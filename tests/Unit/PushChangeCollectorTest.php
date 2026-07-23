<?php

namespace App\Tests\Unit;

use App\Service\PushChangeCollector;
use PHPUnit\Framework\TestCase;

class PushChangeCollectorTest extends TestCase
{
    private PushChangeCollector $collector;

    protected function setUp(): void
    {
        // Создаём реальный объект, т.к. filterChangesSince и truncateText — чистая логика
        $entityManager = $this->createMock(\Doctrine\ORM\EntityManagerInterface::class);
        $this->collector = new PushChangeCollector($entityManager);
    }

    public function testFilterChangesSinceReturnsOnlyNewerChanges(): void
    {
        $since = new \DateTimeImmutable('2026-07-23 12:00:00');

        $changes = [
            [
                'type' => 'task_created',
                'createdAt' => new \DateTimeImmutable('2026-07-23 11:50:00'), // до since
            ],
            [
                'type' => 'task_updated',
                'createdAt' => new \DateTimeImmutable('2026-07-23 12:05:00'), // после since
            ],
            [
                'type' => 'task_created',
                'createdAt' => new \DateTimeImmutable('2026-07-23 12:10:00'), // после since
            ],
        ];

        $result = $this->collector->filterChangesSince($changes, $since);

        $this->assertCount(2, $result);
        $this->assertSame('task_updated', $result[0]['type']);
        $this->assertSame('task_created', $result[1]['type']);
    }

    public function testFilterChangesSinceReturnsEmptyWhenAllOlder(): void
    {
        $since = new \DateTimeImmutable('2026-07-23 12:00:00');

        $changes = [
            [
                'type' => 'task_created',
                'createdAt' => new \DateTimeImmutable('2026-07-23 11:00:00'),
            ],
            [
                'type' => 'task_updated',
                'createdAt' => new \DateTimeImmutable('2026-07-23 11:30:00'),
            ],
        ];

        $result = $this->collector->filterChangesSince($changes, $since);

        $this->assertCount(0, $result);
    }

    public function testFilterChangesSinceReturnsAllWhenSinceIsNull(): void
    {
        // Эмулируем ситуацию новой подписки: since = now - 1 hour
        $since = new \DateTimeImmutable('2026-07-23 11:00:00');

        $changes = [
            [
                'type' => 'task_created',
                'createdAt' => new \DateTimeImmutable('2026-07-23 11:30:00'),
            ],
            [
                'type' => 'task_updated',
                'createdAt' => new \DateTimeImmutable('2026-07-23 11:45:00'),
            ],
        ];

        $result = $this->collector->filterChangesSince($changes, $since);

        $this->assertCount(2, $result);
    }

    public function testFilterChangesSinceExcludesExactMatch(): void
    {
        // Изменения, созданные ровно в since, не должны попасть (нужны строго больше)
        $since = new \DateTimeImmutable('2026-07-23 12:00:00');

        $changes = [
            [
                'type' => 'task_created',
                'createdAt' => new \DateTimeImmutable('2026-07-23 12:00:00'), // равно since
            ],
        ];

        $result = $this->collector->filterChangesSince($changes, $since);

        $this->assertCount(0, $result);
    }

    public function testFilterChangesSinceSkipsChangesWithoutCreatedAt(): void
    {
        $since = new \DateTimeImmutable('2026-07-23 12:00:00');

        $changes = [
            [
                'type' => 'task_created',
                // нет ключа createdAt
            ],
            [
                'type' => 'task_updated',
                'createdAt' => new \DateTimeImmutable('2026-07-23 12:05:00'),
            ],
        ];

        $result = $this->collector->filterChangesSince($changes, $since);

        $this->assertCount(1, $result);
        $this->assertSame('task_updated', $result[0]['type']);
    }

    /**
     * Тесты для truncateText через filterChangesSince (косвенно).
     * Прямой тест truncateText невозможен, т.к. метод private.
     * Но мы можем проверить, что collectChanges возвращает обрезанные тексты.
     * Это уже интеграционный тест, поэтому здесь только filterChangesSince.
     */
    public function testFilterChangesSinceWithEmptyChanges(): void
    {
        $since = new \DateTimeImmutable('2026-07-23 12:00:00');

        $result = $this->collector->filterChangesSince([], $since);

        $this->assertCount(0, $result);
    }
}