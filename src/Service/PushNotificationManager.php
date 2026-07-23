<?php

namespace App\Service;

use App\Entity\PushSubscription;
use Psr\Log\LoggerInterface;

/**
 * Управляет отправкой push-уведомлений подписчикам проекта.
 *
 * Содержит основную бизнес-логику: определение периода сбора изменений,
 * фильтрацию по подписке, исключение автора изменений.
 *
 */
class PushNotificationManager
{
    public function __construct(
        private PushChangeCollector $changeCollector,
        private PushNotificationService $pushService,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * Обработать проект: собрать изменения и отправить уведомления подписчикам.
     *
     * @param PushSubscription[] $subscriptions
     * @return int Количество отправленных уведомлений
     */
    public function processProject(\App\Entity\Project $project, array $subscriptions, \DateTimeImmutable $now): int
    {
        $totalSent = 0;

        if (empty($subscriptions)) {
            $this->logger->info('PushNotificationManager: no subscriptions for project', [
                'project' => $project->getName(),
            ]);
            return 0;
        }

        // Проверяем, были ли вообще изменения в проекте
        $lastProjectChange = $this->changeCollector->getLastProjectChange($project);

        if ($lastProjectChange === null) {
            $this->logger->info('PushNotificationManager: no changes found in project', [
                'project' => $project->getName(),
            ]);
            return 0;
        }

        // Вычисляем, сколько минут назад было последнее изменение
        $minutesAgo = ($now->getTimestamp() - $lastProjectChange->getTimestamp()) / 60;

        $this->logger->info('PushNotificationManager: project last change', [
            'project' => $project->getName(),
            'minutes_ago' => (int) $minutesAgo,
        ]);

        // Если последнее изменение от 10 до 40 минут назад
        if ($minutesAgo < 10 || $minutesAgo > 40) {
            $this->logger->info('PushNotificationManager: skipping (outside 10-40 min window)', [
                'project' => $project->getName(),
                'minutes_ago' => (int) $minutesAgo,
            ]);
            return 0;
        }

        // Определяем минимальную дату last_changes среди всех подписок,
        // чтобы собрать изменения за весь необходимый период
        $sinceMin = null;
        foreach ($subscriptions as $subscription) {
            $lastChanges = $subscription->getLastChanges();
            if ($sinceMin === null || $lastChanges < $sinceMin) {
                $sinceMin = $lastChanges;
            }
        }

        // Собираем все изменения в проекте от минимального since до now
        $allChanges = $this->changeCollector->collectChanges(
            $project,
            $sinceMin,
            $now,
        );

        if (empty($allChanges)) {
            $this->logger->info('PushNotificationManager: no changes in period', [
                'project' => $project->getName(),
            ]);
            return 0;
        }

        $this->logger->info('PushNotificationManager: found changes', [
            'project' => $project->getName(),
            'count' => count($allChanges),
        ]);

        // Обрабатываем каждую подписку индивидуально
        foreach ($subscriptions as $subscription) {
            $sent = $this->processSubscription($subscription, $allChanges, $now);
            $totalSent += $sent;
        }

        return $totalSent;
    }

    /**
     * Обработать одну подписку: отфильтровать изменения и отправить уведомления.
     *
     * @param PushSubscription $subscription
     * @param array $allChanges Все изменения в проекте за последний час
     * @param \DateTimeImmutable $now
     * @return int 1 если отправлено, 0 если нет
     */
    public function processSubscription(PushSubscription $subscription, array $allChanges, \DateTimeImmutable $now): int
    {
        // Фильтруем изменения, которые произошли после last_changes подписки
        $newChanges = $this->changeCollector->filterChangesSince($allChanges, $subscription->getLastChanges());

        if (empty($newChanges)) {
            $this->logger->info('PushNotificationManager: no new changes for subscription', [
                'subscription_id' => $subscription->getId(),
                'user_id' => $subscription->getUser()?->getId(),
            ]);
            $subscription->setLastChanges($now);
            return 0;
        }

        $this->logger->info('PushNotificationManager: new changes for subscription', [
            'subscription_id' => $subscription->getId(),
            'user_id' => $subscription->getUser()?->getId(),
            'count' => count($newChanges),
        ]);

        // Группируем изменения по автору
        $changesByUser = [];
        foreach ($newChanges as $change) {
            $userId = $change['user']['id'] ?? 0;
            if (!isset($changesByUser[$userId])) {
                $changesByUser[$userId] = [];
            }
            $changesByUser[$userId][] = $change;
        }

        // Отправляем уведомления, исключая автора изменений
        foreach ($changesByUser as $authorId => $userChanges) {
            $this->pushService->sendBulkNotifications([$subscription], $userChanges, $authorId);
        }

        // Обновляем last_changes для этой подписки
        $subscription->setLastChanges($now);

        return 1;
    }
}