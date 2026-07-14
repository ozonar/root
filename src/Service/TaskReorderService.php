<?php

namespace App\Service;

use App\Entity\Task;
use Doctrine\ORM\EntityManagerInterface;

class TaskReorderService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * Пересчитывает order для всех sibling-задач (с тем же parent и page).
     */
    public function reorderSiblings(Task $task): void
    {
        $parent = $task->getParent();
        $page = $task->getPage();

        $siblings = $this->entityManager->getRepository(Task::class)
            ->findBy(['parent' => $parent, 'page' => $page], ['order' => 'ASC']);

        $order = 0;
        foreach ($siblings as $sibling) {
            $sibling->setOrder($order++);
        }

        $this->entityManager->flush();
    }

    /**
     * Вставляет задачу в указанную позицию среди всех sibling-задач
     * (задачи с тем же parent и page) и пересчитывает order для всех.
     */
    public function reorderWithTargetPosition(Task $task, int $targetOrder): void
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
}