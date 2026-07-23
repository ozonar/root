<?php

namespace App\Service;

use App\Entity\PushSubscription;
use Doctrine\ORM\EntityManagerInterface;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;
use Psr\Log\LoggerInterface;

class PushNotificationService
{
    private WebPush $webPush;
    private string $vapidPublicKey;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger,
        string $vapidPublicKey,
        string $vapidPrivateKey,
        string $vapidSubject,
    ) {
        $this->vapidPublicKey = $vapidPublicKey;

        $auth = [
            'VAPID' => [
                'publicKey' => $vapidPublicKey,
                'privateKey' => $vapidPrivateKey,
                'subject' => $vapidSubject,
            ],
        ];

        $this->webPush = new WebPush($auth);
        $this->webPush->setAutomaticPadding(false);
    }

    /**
     * @return array{publicKey: string, privateKey: string}
     */
    public function generateVapidKeys(): array
    {
        $keys = \Minishlink\WebPush\VAPID::createVapidKeys();

        return [
            'publicKey' => $keys['publicKey'],
            'privateKey' => $keys['privateKey'],
        ];
    }

    public function getPublicKey(): string
    {
        return $this->vapidPublicKey;
    }

    /**
     * Отправка push-уведомления одной подписке.
     */
    public function sendNotification(PushSubscription $subscription, string $title, string $body, array $data = []): bool
    {
        $payload = json_encode([
            'title' => $title,
            'body' => $body,
            'data' => $data,
            'icon' => '/favicon.ico',
            'badge' => '/favicon.ico',
            'tag' => 'checker-update',
        ]);

        if ($payload === false) {
            return false;
        }

        $sub = Subscription::create([
            'endpoint' => $subscription->getEndpoint(),
            'publicKey' => $subscription->getP256dh(),
            'authToken' => $subscription->getAuthToken(),
        ]);

        try {
            $this->webPush->queueNotification($sub, $payload);
            $results = $this->webPush->flush();

            foreach ($results as $result) {
                if ($result->isSuccess()) {
                    return true;
                }

                $endpoint = $result->getEndpoint();
                $reason = $result->getReason();

                $this->logger->error('Failed to send push notification: ' . $reason, [
                    'endpoint' => $endpoint,
                    'subscription_id' => $subscription->getId(),
                ]);

                if ($result->isSubscriptionExpired()) {
                    $this->entityManager->remove($subscription);
                    $this->entityManager->flush();
                }

                return false;
            }

            return false;
        } catch (\Exception $e) {
            $this->logger->error('Failed to send push notification: ' . $e->getMessage(), [
                'endpoint' => $subscription->getEndpoint(),
                'subscription_id' => $subscription->getId(),
            ]);

            return false;
        }
    }

    /**
     * Отправка уведомлений всем подписчикам проекта, кроме указанного пользователя.
     *
     * @param PushSubscription[] $subscriptions
     * @param array $changes Массив изменений: [['type' => 'task_created'|'task_updated', 'task' => [...], 'page' => [...], 'user' => [...]]]
     */
    public function sendBulkNotifications(array $subscriptions, array $changes, int $excludeUserId): void
    {
        foreach ($subscriptions as $subscription) {
            $subscriber = $subscription->getUser();
            if ($subscriber && $subscriber->getId() === $excludeUserId) {
                continue;
            }

            $projectName = $subscription->getProject()?->getName() ?? 'Проект';

            $createdTasks = [];
            $updatedTasks = [];
            $changedPages = [];

            foreach ($changes as $change) {
                $type = $change['type'] ?? '';
                $taskData = $change['task'] ?? [];
                $pageData = $change['page'] ?? [];

                $pageTitle = $pageData['title'] ?? 'страница';
                $changedPages[$pageData['id'] ?? 0] = $pageTitle;

                if ($type === 'task_created') {
                    $createdTasks[] = $taskData['text'] ?? 'новая задача';
                } elseif ($type === 'task_updated') {
                    $updatedTasks[] = $taskData['text'] ?? 'задача';
                }
            }

            $parts = [];
            if (count($createdTasks) > 0) {
                $parts[] = 'Создано: ' . implode(', ', array_slice($createdTasks, 0, 3));
                if (count($createdTasks) > 3) {
                    $parts[count($parts) - 1] .= ' и ещё ' . (count($createdTasks) - 3);
                }
            }
            if (count($updatedTasks) > 0) {
                $parts[] = 'Изменено: ' . implode(', ', array_slice($updatedTasks, 0, 3));
                if (count($updatedTasks) > 3) {
                    $parts[count($parts) - 1] .= ' и ещё ' . (count($updatedTasks) - 3);
                }
            }

            $body = implode('. ', $parts);
            if (empty($body)) {
                $body = 'Есть изменения в проекте';
            }

            $pageIds = array_keys($changedPages);
            $data = [
                'projectId' => $subscription->getProject()?->getId(),
                'pageIds' => $pageIds,
            ];

            $this->sendNotification($subscription, $projectName, $body, $data);
        }
    }
}