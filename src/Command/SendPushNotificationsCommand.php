<?php

namespace App\Command;

use App\Repository\PushSubscriptionRepository;
use App\Service\PushNotificationManager;
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
        private PushNotificationManager $notificationManager,
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

            $sent = $this->notificationManager->processProject($project, $projectSubs, $now);
            $totalSent += $sent;

            if ($sent > 0) {
                $io->success(sprintf('Sent %d notifications for project "%s"', $sent, $project->getName()));
            } else {
                $io->text(sprintf('No notifications sent for project "%s"', $project->getName()));
            }
        }

        // Сохраняем обновлённые last_changes
        $this->entityManager->flush();

        $io->success(sprintf('Done. Total notifications sent: %d', $totalSent));

        return Command::SUCCESS;
    }
}