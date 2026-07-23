<?php

namespace App\Controller;

use App\Entity\Project;
use App\Entity\PushSubscription;
use App\Repository\PushSubscriptionRepository;
use App\Service\PushNotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/push')]
class PushSubscriptionController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private PushNotificationService $pushService,
        private PushSubscriptionRepository $subscriptionRepo,
    ) {
    }

    /**
     * Получить публичный VAPID ключ для подписки.
     */
    #[Route('/vapid-public-key', name: 'api_push_vapid_public_key', methods: ['GET'])]
    public function vapidPublicKey(): JsonResponse
    {
        return $this->json([
            'publicKey' => $this->pushService->getPublicKey(),
        ]);
    }

    /**
     * Подписаться на уведомления проекта.
     */
    #[Route('/subscribe', name: 'api_push_subscribe', methods: ['POST'])]
    public function subscribe(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true);
        if (!$data) {
            return $this->json(['error' => 'Invalid JSON'], Response::HTTP_BAD_REQUEST);
        }

        $projectId = $data['projectId'] ?? null;
        $endpoint = $data['endpoint'] ?? null;
        $p256dh = $data['keys']['p256dh'] ?? null;
        $authToken = $data['keys']['auth'] ?? null;

        if (!$projectId || !$endpoint || !$p256dh || !$authToken) {
            return $this->json(['error' => 'Missing required fields: projectId, endpoint, keys.p256dh, keys.auth'], Response::HTTP_BAD_REQUEST);
        }

        $project = $this->entityManager->getRepository(Project::class)->find($projectId);
        if (!$project) {
            return $this->json(['error' => 'Project not found'], Response::HTTP_NOT_FOUND);
        }

        // Проверяем, что пользователь — участник проекта
        $isMember = $this->entityManager
            ->getRepository(\App\Entity\UserProject::class)
            ->findOneBy(['user' => $user, 'project' => $project]);
        if (!$isMember) {
            return $this->json(['error' => 'Access denied'], Response::HTTP_FORBIDDEN);
        }

        // Ищем существующую подписку
        $existing = $this->subscriptionRepo->findOneByUserAndProject($user, $project);
        if ($existing) {
            // Обновляем существующую
            $existing->setEndpoint($endpoint);
            $existing->setP256dh($p256dh);
            $existing->setAuthToken($authToken);
            $this->entityManager->flush();

            return $this->json(['success' => true, 'subscribed' => true]);
        }

        // Создаём новую подписку
        $subscription = new PushSubscription();
        $subscription->setUser($user);
        $subscription->setProject($project);
        $subscription->setEndpoint($endpoint);
        $subscription->setP256dh($p256dh);
        $subscription->setAuthToken($authToken);
        $subscription->setLastChanges(new \DateTimeImmutable());

        $this->entityManager->persist($subscription);
        $this->entityManager->flush();

        return $this->json(['success' => true, 'subscribed' => true], Response::HTTP_CREATED);
    }

    /**
     * Отписаться от уведомлений проекта.
     */
    #[Route('/unsubscribe', name: 'api_push_unsubscribe', methods: ['POST'])]
    public function unsubscribe(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true);
        $projectId = $data['projectId'] ?? null;

        if (!$projectId) {
            return $this->json(['error' => 'Missing projectId'], Response::HTTP_BAD_REQUEST);
        }

        $project = $this->entityManager->getRepository(Project::class)->find($projectId);
        if (!$project) {
            return $this->json(['error' => 'Project not found'], Response::HTTP_NOT_FOUND);
        }

        $subscription = $this->subscriptionRepo->findOneByUserAndProject($user, $project);
        if ($subscription) {
            $this->entityManager->remove($subscription);
            $this->entityManager->flush();
        }

        return $this->json(['success' => true, 'unsubscribed' => true]);
    }

    /**
     * Проверить статус подписки на проект.
     */
    #[Route('/status', name: 'api_push_status', methods: ['GET'])]
    public function status(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $projectId = $request->query->get('projectId');
        if (!$projectId) {
            return $this->json(['error' => 'Missing projectId'], Response::HTTP_BAD_REQUEST);
        }

        $project = $this->entityManager->getRepository(Project::class)->find($projectId);
        if (!$project) {
            return $this->json(['error' => 'Project not found'], Response::HTTP_NOT_FOUND);
        }

        $subscription = $this->subscriptionRepo->findOneByUserAndProject($user, $project);

        return $this->json([
            'subscribed' => $subscription !== null,
        ]);
    }
}