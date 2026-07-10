<?php

namespace App\Controller;

use App\Entity\Task;
use App\Entity\Status;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/tasks')]
class TaskController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('/{id}', name: 'api_task_get', methods: ['GET'])]
    public function get(int $id): JsonResponse
    {
        $task = $this->entityManager->getRepository(Task::class)->find($id);
        if (!$task) {
            return $this->json(['error' => 'Task not found'], Response::HTTP_NOT_FOUND);
        }

        return $this->json(['task' => $this->serializeTask($task)]);
    }

    #[Route('/{id}', name: 'api_task_update', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $task = $this->entityManager->getRepository(Task::class)->find($id);
        if (!$task) {
            return $this->json(['error' => 'Task not found'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['text'])) {
            $task->setText($data['text']);
        }
        if (isset($data['description'])) {
            $task->setDescription($data['description']);
        }
        if (array_key_exists('parentId', $data)) {
            $parent = $data['parentId']
                ? $this->entityManager->getRepository(Task::class)->find($data['parentId'])
                : null;
            $task->setParent($parent);
        }
        if (isset($data['isPriority'])) {
            $task->setPriority((bool) $data['isPriority']);
        }
        if (isset($data['status'])) {
            $status = $this->entityManager->getRepository(Status::class)
                ->findOneBy(['systemName' => $data['status']]);
            if ($status) {
                $task->setStatus($status);
            }
        }
        if (array_key_exists('assignee', $data)) {
            if ($data['assignee']) {
                $assignee = $this->entityManager->getRepository(User::class)
                    ->findOneBy(['email' => $data['assignee']]);
                $task->setAssignee($assignee);
            } else {
                $task->setAssignee(null);
            }
        }

        $task->setUpdatedBy($this->getUser());
        $task->setUpdatedAt(new \DateTimeImmutable());
        $task->getPage()->setEditedAt(new \DateTimeImmutable());

        $this->entityManager->flush();

        return $this->json(['success' => true]);
    }

    #[Route('/{id}/status', name: 'api_task_status', methods: ['PUT'])]
    public function updateStatus(int $id, Request $request): JsonResponse
    {
        $task = $this->entityManager->getRepository(Task::class)->find($id);
        if (!$task) {
            return $this->json(['error' => 'Task not found'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);
        $statusSystemName = $data['status'] ?? null;

        if ($statusSystemName) {
            $status = $this->entityManager->getRepository(Status::class)
                ->findOneBy(['systemName' => $statusSystemName]);
            if ($status) {
                $task->setStatus($status);
            }
        }

        $task->setUpdatedBy($this->getUser());
        $task->setUpdatedAt(new \DateTimeImmutable());
        $task->getPage()->setEditedAt(new \DateTimeImmutable());
        $this->entityManager->flush();

        return $this->json(['success' => true]);
    }

    #[Route('/{id}/move', name: 'api_task_move', methods: ['PUT'])]
    public function move(int $id, Request $request): JsonResponse
    {
        $task = $this->entityManager->getRepository(Task::class)->find($id);
        if (!$task) {
            return $this->json(['error' => 'Task not found'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);

        // Принимаем parentId (null для корневого уровня) и position (порядковый номер среди sibling)
        if (array_key_exists('parentId', $data) && isset($data['position'])) {
            $parent = $data['parentId']
                ? $this->entityManager->getRepository(Task::class)->find($data['parentId'])
                : null;
            $targetOrder = (int) $data['position'];

            $task->setParent($parent);
            $task->setUpdatedAt(new \DateTimeImmutable());
            $task->getPage()->setEditedAt(new \DateTimeImmutable());

            $this->reorderTasks($task, $targetOrder);
        }

        // Возвращаем обновлённый список задач страницы, чтобы фронт обновил массив tasks
        $page = $task->getPage();
        $allTasks = $this->entityManager->getRepository(Task::class)
            ->findBy(['page' => $page], ['order' => 'ASC']);

        $tasks = array_map(fn(Task $t) => $this->serializeTask($t), $allTasks);

        return $this->json(['success' => true, 'tasks' => $tasks]);
    }

    /**
     * Вставляет задачу в указанную позицию среди всех sibling-задач
     * (задачи с тем же parent и page) и пересчитывает order для всех.
     */
    private function reorderTasks(Task $task, int $targetOrder): void
    {
        $parent = $task->getParent();
        $page = $task->getPage();

        // Берём все sibling-задачи, исключая текущую
        $allSiblings = $this->entityManager->getRepository(Task::class)
            ->findBy(['parent' => $parent, 'page' => $page], ['order' => 'ASC']);

        // Собираем массив задач, исключая текущую (чтобы потом вставить в нужное место)
        $siblings = [];
        foreach ($allSiblings as $sibling) {
            if ($sibling->getId() !== $task->getId()) {
                $siblings[] = $sibling;
            }
        }

        // Вставляем задачу в нужную позицию
        array_splice($siblings, $targetOrder, 0, [$task]);

        // Пересчитываем order для всех
        $order = 0;
        foreach ($siblings as $sibling) {
            $sibling->setOrder($order++);
        }

        $this->entityManager->flush();
    }

    #[Route('/{id}', name: 'api_task_delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $task = $this->entityManager->getRepository(Task::class)->find($id);
        if (!$task) {
            return $this->json(['error' => 'Task not found'], Response::HTTP_NOT_FOUND);
        }

        // Delete children recursively
        $this->deleteChildren($task);

        $this->entityManager->remove($task);
        $this->entityManager->flush();

        return $this->json(['success' => true]);
    }

    private function serializeTask(Task $task): array
    {
        return [
            'id' => $task->getId(),
            'pageId' => $task->getPage()?->getId(),
            'parentId' => $task->getParent()?->getId(),
            'text' => $task->getText(),
            'description' => $task->getDescription(),
            'status' => $task->getStatus()?->getSystemName(),
            'statusName' => $task->getStatus()?->getName(),
            'statusIcon' => $task->getStatus()?->getIcon(),
            'isPriority' => $task->isPriority(),
            'order' => $task->getOrder(),
            'assignee' => $task->getAssignee()?->getEmail(),
            'assigneeName' => $task->getAssignee()?->getName(),
            'createdAt' => $task->getCreatedAt()->format('c'),
            'updatedAt' => $task->getUpdatedAt()->format('c'),
        ];
    }

    private function deleteChildren(Task $task): void
    {
        $children = $this->entityManager
            ->getRepository(Task::class)
            ->findBy(['parent' => $task]);

        foreach ($children as $child) {
            $this->deleteChildren($child);
            $this->entityManager->remove($child);
        }
    }
}