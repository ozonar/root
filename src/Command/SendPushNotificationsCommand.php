<?php

namespace App\Command;

use App\Entity\Page;
use App\Entity\Project;
use App\Entity\Task;
use App\Repository\PushSubscriptionRepository;
use App\Service\PushNotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:send-push-notifications',
    description: 'Check for project changes and send push notifications to subscribers',
)]
class SendPushNotificationsCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private PushNotificationService $pushService,
        private PushSubscriptionRepository $subscriptionRepo,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $now = new \DateTimeImmutable();

        // Получаем все подписки с проектами
        $subscriptions = $this->subscriptionRepo->findProjectsWithActiveSubscriptions();

        // Группируем подписки по проектам
        $projectSubscriptions = [];
        foreach ($subscriptions as $sub) {
            $project = $sub->getProject();
            if ($project) {
                $projectId = $project->getId();
                if (!isset($projectSubscriptions[$projectId])) {
                    $projectSubscriptions[$projectId] = [
                        'project' => $project,
                        'subscriptions' => [],
                    ];
                }
                $projectSubscriptions[$projectId]['subscriptions'][] = $sub;
            }
        }

        $io->info(sprintf('Found %d projects with active subscriptions', count($projectSubscriptions)));

        $totalSent = 0;

        foreach ($projectSubscriptions as $item) {
            $project = $item['project'];
            $projectSubs = $item['subscriptions'];

            // Находим самое раннее last_changes среди подписок проекта
            $minLastChanges = null;
            foreach ($projectSubs as $sub) {
                $lc = $sub->getLastChanges();
                if ($lc === null) {
                    $minLastChanges = null;
                    break;
                }
                if ($minLastChanges === null || $lc < $minLastChanges) {
                    $minLastChanges = $lc;
                }
            }

            // Проверяем, когда были последние изменения в проекте
            $lastProjectChange = $this->getLastProjectChange($project);

            if ($lastProjectChange === null) {
                $io->text(sprintf('Project "%s": no changes found', $project->getName()));
                continue;
            }

            // Вычисляем, сколько минут назад было последнее изменение
            $minutesAgo = ($now->getTimestamp() - $lastProjectChange->getTimestamp()) / 60;

            $io->text(sprintf(
                'Project "%s": last change %d minutes ago (min last_changes: %s)',
                $project->getName(),
                (int) $minutesAgo,
                $minLastChanges?->format('c') ?? 'never'
            ));

            // Если последнее изменение от 10 до 40 минут назад
            if ($minutesAgo >= 10 && $minutesAgo <= 40) {
                $io->text(sprintf('  -> Processing changes for project "%s"', $project->getName()));

                // Определяем временной диапазон для сбора изменений
                $changesFrom = $minLastChanges ?? new \DateTimeImmutable('-1 hour');
                $changesTo = $now;

                // Собираем все изменения в проекте от last_changes до now
                $changes = $this->collectProjectChanges($project, $changesFrom, $changesTo);

                if (empty($changes)) {
                    $io->text('  -> No changes found in period');
                    // Всё равно обновляем last_changes, чтобы не проверять снова
                    $this->updateLastChanges($projectSubs, $now);
                    continue;
                }

                $io->text(sprintf('  -> Found %d changes', count($changes)));

                // Группируем изменения по пользователям
                $changesByUser = [];
                foreach ($changes as $change) {
                    $userId = $change['user']['id'] ?? 0;
                    if (!isset($changesByUser[$userId])) {
                        $changesByUser[$userId] = [];
                    }
                    $changesByUser[$userId][] = $change;
                }

                // Отправляем уведомления каждому подписчику, исключая автора изменений
                foreach ($changesByUser as $authorId => $userChanges) {
                    $this->pushService->sendBulkNotifications($projectSubs, $userChanges, $authorId);
                    $totalSent += count($projectSubs);
                }

                // Обновляем last_changes для всех подписок проекта
                $this->updateLastChanges($projectSubs, $now);

                $io->success(sprintf('Sent notifications for project "%s"', $project->getName()));
            } else {
                $io->text(sprintf('  -> Skipping (outside 10-40 min window)'));
            }
        }

        $io->success(sprintf('Done. Total notifications sent: %d', $totalSent));

        return Command::SUCCESS;
    }

    /**
     * Получить время последнего изменения в проекте.
     */
    private function getLastProjectChange(Project $project): ?\DateTimeImmutable
    {
        // Проверяем страницы проекта
        $pages = $this->entityManager
            ->getRepository(Page::class)
            ->findBy(['project' => $project]);

        $latest = null;
        foreach ($pages as $page) {
            $editedAt = $page->getEditedAt();
            if ($editedAt && ($latest === null || $editedAt > $latest)) {
                $latest = $editedAt;
            }
            $createdAt = $page->getCreatedAt();
            if ($createdAt && ($latest === null || $createdAt > $latest)) {
                $latest = $createdAt;
            }
        }

        // Проверяем задачи на этих страницах
        foreach ($pages as $page) {
            $tasks = $this->entityManager
                ->getRepository(Task::class)
                ->findBy(['page' => $page]);

            foreach ($tasks as $task) {
                $updatedAt = $task->getUpdatedAt();
                if ($updatedAt && ($latest === null || $updatedAt > $latest)) {
                    $latest = $updatedAt;
                }
                $createdAt = $task->getCreatedAt();
                if ($createdAt && ($latest === null || $createdAt > $latest)) {
                    $latest = $createdAt;
                }
            }
        }

        return $latest;
    }

    /**
     * Собрать все изменения в проекте за указанный период.
     *
     * @return array<array{type: string, task: array, page: array, user: array}>
     */
    private function collectProjectChanges(Project $project, \DateTimeImmutable $from, \DateTimeImmutable $to): array
    {
        $changes = [];

        $pages = $this->entityManager
            ->getRepository(Page::class)
            ->findBy(['project' => $project]);

        foreach ($pages as $page) {
            $tasks = $this->entityManager
                ->getRepository(Task::class)
                ->findBy(['page' => $page]);

            foreach ($tasks as $task) {
                $updatedAt = $task->getUpdatedAt();
                $createdAt = $task->getCreatedAt();

                // Проверяем, была ли задача создана в период
                if ($createdAt >= $from && $createdAt <= $to) {
                    $changes[] = [
                        'type' => 'task_created',
                        'task' => [
                            'id' => $task->getId(),
                            'text' => $this->truncateText($task->getText(), 50),
                        ],
                        'page' => [
                            'id' => $page->getId(),
                            'title' => $page->getTitle(),
                        ],
                        'user' => [
                            'id' => $task->getCreatedBy()?->getId(),
                            'name' => $task->getCreatedBy()?->getDisplayName(),
                        ],
                    ];
                }

                // Проверяем, была ли задача обновлена в период (но не создана)
                if ($updatedAt >= $from && $updatedAt <= $to && $updatedAt != $createdAt) {
                    $changes[] = [
                        'type' => 'task_updated',
                        'task' => [
                            'id' => $task->getId(),
                            'text' => $this->truncateText($task->getText(), 50),
                        ],
                        'page' => [
                            'id' => $page->getId(),
                            'title' => $page->getTitle(),
                        ],
                        'user' => [
                            'id' => $task->getUpdatedBy()?->getId(),
                            'name' => $task->getUpdatedBy()?->getDisplayName(),
                        ],
                    ];
                }
            }
        }

        return $changes;
    }

    /**
     * Обновить last_changes для всех подписок проекта.
     *
     * @param \App\Entity\PushSubscription[] $subscriptions
     */
    private function updateLastChanges(array $subscriptions, \DateTimeImmutable $now): void
    {
        foreach ($subscriptions as $sub) {
            $sub->setLastChanges($now);
        }
        $this->entityManager->flush();
    }

    private function truncateText(?string $text, int $maxLength): string
    {
        if ($text === null || $text === '') {
            return '(пусто)';
        }
        $text = strip_tags($text);
        if (mb_strlen($text) > $maxLength) {
            return mb_substr($text, 0, $maxLength) . '...';
        }
        return $text;
    }
}