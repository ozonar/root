<?php

namespace App\Service;

use App\Entity\Page;
use App\Entity\Project;
use App\Entity\Task;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Собирает изменения (созданные/обновлённые задачи) в проекте за указанный период.
 *
 * Этот сервис можно замокать в юнит-тестах.
 */
class PushChangeCollector
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * Получить время последнего изменения в проекте.
     */
    public function getLastProjectChange(Project $project): ?\DateTimeImmutable
    {
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
     * @return array<array{type: string, task: array, page: array, user: array, createdAt: \DateTimeImmutable}>
     */
    public function collectChanges(Project $project, \DateTimeImmutable $from, \DateTimeImmutable $to): array
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
                        'createdAt' => $createdAt,
                    ];
                }

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
                        'createdAt' => $updatedAt,
                    ];
                }
            }
        }

        return $changes;
    }

    /**
     * Отфильтровать изменения, которые произошли после указанной даты.
     *
     * @param array $changes
     * @param \DateTimeImmutable $since
     * @return array
     */
    public function filterChangesSince(array $changes, \DateTimeImmutable $since): array
    {
        $filtered = [];
        foreach ($changes as $change) {
            if (isset($change['createdAt']) && $change['createdAt'] > $since) {
                $filtered[] = $change;
            }
        }
        return $filtered;
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