<?php

namespace App\Controller;

use App\Entity\Page;
use App\Entity\Task;
use App\Entity\Status;
use App\Entity\UserProject;
use App\Service\StatusService;
use App\Service\TaskReorderService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/pages')]
class PageController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private StatusService $statusService,
        private TaskReorderService $taskReorderService,
    ) {
    }

    #[Route('/{id}', name: 'api_page_get', methods: ['GET'])]
    public function get(int $id): JsonResponse
    {
        $page = $this->entityManager->getRepository(Page::class)->find($id);
        if (!$page) {
            return $this->json(['error' => 'Page not found'], Response::HTTP_NOT_FOUND);
        }

        $tasks = $this->entityManager
            ->getRepository(Task::class)
            ->findBy(['page' => $page], ['order' => 'ASC']);

        $project = $page->getProject();
        $statuses = $this->entityManager
            ->getRepository(Status::class)
            ->findBy(['project' => $project, 'active' => true]);

        $tasksResult = [];
        foreach ($tasks as $task) {
            $tasksResult[] = $this->serializeTask($task);
        }

        $statusesResult = [];
        foreach ($statuses as $status) {
            $statusesResult[] = [
                'id' => $status->getId(),
                'systemName' => $status->getSystemName(),
                'name' => $status->getName(),
                'icon' => $status->getIcon(),
            ];
        }

        return $this->json([
            'page' => [
                'id' => $page->getId(),
                'title' => $page->getTitle(),
                'editedAt' => $page->getEditedAt()?->format('c'),
                'createdAt' => $page->getCreatedAt()->format('c'),
            ],
            'tasks' => $tasksResult,
            'statuses' => $statusesResult,
        ]);
    }

    #[Route('/{id}', name: 'api_page_update', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $page = $this->entityManager->getRepository(Page::class)->find($id);
        if (!$page) {
            return $this->json(['error' => 'Page not found'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['title'])) {
            $page->setTitle($data['title']);
        }

        $page->setEditedAt(new \DateTimeImmutable());
        $this->entityManager->flush();

        return $this->json(['success' => true]);
    }

    #[Route('/{id}', name: 'api_page_delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $page = $this->entityManager->getRepository(Page::class)->find($id);
        if (!$page) {
            return $this->json(['error' => 'Page not found'], Response::HTTP_NOT_FOUND);
        }

        $project = $page->getProject();
        $user = $this->getUser();

        // Проверяем, что пользователь имеет любой доступ к проекту
        $userProject = $this->entityManager
            ->getRepository(UserProject::class)
            ->findOneBy(['user' => $user, 'project' => $project]);

        if (!$userProject) {
            return $this->json(['error' => 'Access denied'], Response::HTTP_FORBIDDEN);
        }

        // Удаляем все задачи страницы
        $tasks = $this->entityManager
            ->getRepository(Task::class)
            ->findBy(['page' => $page]);

        foreach ($tasks as $task) {
            $this->entityManager->remove($task);
        }

        $this->entityManager->remove($page);
        $this->entityManager->flush();

        return $this->json(['success' => true]);
    }

    #[Route('/{id}/tasks', name: 'api_page_tasks_create', methods: ['POST'])]
    public function createTask(int $id, Request $request): JsonResponse
    {
        $page = $this->entityManager->getRepository(Page::class)->find($id);
        if (!$page) {
            return $this->json(['error' => 'Page not found'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);

        $task = new Task();
        $task->setPage($page);
        $task->setText($data['text'] ?? 'Новая задача');
        $task->setOrder($data['order'] ?? 0);
        $task->setCreatedBy($this->getUser());
        $task->setAssignee($this->getUser());

        if (isset($data['parentId'])) {
            $parent = $this->entityManager->getRepository(Task::class)->find($data['parentId']);
            if ($parent) {
                $task->setParent($parent);
            }
        }

        $this->entityManager->persist($task);
        $this->entityManager->flush();

        // Если передан position — вставляем в конкретную позицию, иначе просто пересчитываем
        if (isset($data['position'])) {
            $this->taskReorderService->reorderWithTargetPosition($task, (int) $data['position']);
        } else {
            $this->taskReorderService->reorderSiblings($task);
        }

        return $this->json([
            'task' => $this->serializeTask($task),
        ], Response::HTTP_CREATED);
    }

    private function serializeTask(Task $task): array
    {
        return [
            'id' => $task->getId(),
            'text' => $task->getText(),
            'description' => $task->getDescription(),
            'status' => $task->getStatus()?->getSystemName(),
            'statusName' => $task->getStatus()?->getName(),
            'statusIcon' => $task->getStatus()?->getIcon(),
            'order' => $task->getOrder(),
            'parentId' => $task->getParent()?->getId(),
            'isPriority' => $task->isPriority(),
            'assignee' => $task->getAssignee()?->getEmail(),
            'assigneeName' => $task->getAssignee()?->getDisplayName(),
            'createdAt' => $task->getCreatedAt()->format('c'),
        ];
    }
}