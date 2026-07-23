<?php

namespace App\Tests\Unit;

use App\Entity\Project;
use App\Entity\PushSubscription;
use App\Service\PushChangeCollector;
use App\Service\PushNotificationManager;
use App\Service\PushNotificationService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class PushNotificationManagerTest extends TestCase
{
    private PushChangeCollector $changeCollector;
    private PushNotificationService $pushService;
    private LoggerInterface $logger;
    private PushNotificationManager $manager;

    protected function setUp(): void
    {
        $this->changeCollector = $this->createMock(PushChangeCollector::class);
        $this->pushService = $this->createMock(PushNotificationService::class);
        $this->logger = $this->createStub(LoggerInterface::class);

        $this->manager = new PushNotificationManager(
            $this->changeCollector,
            $this->pushService,
            $this->logger,
        );
    }

    // ==========================================
    // processProject tests
    // ==========================================

    public function testProcessProjectNoSubscriptions(): void
    {
        $project = new Project();
        $now = new \DateTimeImmutable('2026-07-23 12:00:00');

        $this->changeCollector
            ->expects($this->never())
            ->method('getLastProjectChange');

        $result = $this->manager->processProject($project, [], $now);

        $this->assertSame(0, $result);
    }

    public function testProcessProjectNoChanges(): void
    {
        $project = new Project();
        $now = new \DateTimeImmutable('2026-07-23 12:00:00');

        $sub = new PushSubscription();

        $this->changeCollector
            ->expects($this->once())
            ->method('getLastProjectChange')
            ->with($project)
            ->willReturn(null);

        $result = $this->manager->processProject($project, [$sub], $now);

        $this->assertSame(0, $result);
    }

    public function testProcessProjectOutsideTimeWindowTooOld(): void
    {
        $project = new Project();
        $now = new \DateTimeImmutable('2026-07-23 12:00:00');
        $lastChange = new \DateTimeImmutable('2026-07-23 11:00:00'); // 60 минут назад

        $sub = new PushSubscription();

        $this->changeCollector
            ->expects($this->once())
            ->method('getLastProjectChange')
            ->with($project)
            ->willReturn($lastChange);

        $result = $this->manager->processProject($project, [$sub], $now);

        $this->assertSame(0, $result);
    }

    public function testProcessProjectOutsideTimeWindowTooRecent(): void
    {
        $project = new Project();
        $now = new \DateTimeImmutable('2026-07-23 12:05:00');
        $lastChange = new \DateTimeImmutable('2026-07-23 12:03:00'); // 2 минуты назад

        $sub = new PushSubscription();

        $this->changeCollector
            ->expects($this->once())
            ->method('getLastProjectChange')
            ->with($project)
            ->willReturn($lastChange);

        $result = $this->manager->processProject($project, [$sub], $now);

        $this->assertSame(0, $result);
    }

    public function testProcessProjectWithinTimeWindowNoChanges(): void
    {
        $project = new Project();
        $now = new \DateTimeImmutable('2026-07-23 12:15:00');
        $lastChange = new \DateTimeImmutable('2026-07-23 12:00:00'); // 15 минут назад

        $sub = new PushSubscription();

        $this->changeCollector
            ->expects($this->once())
            ->method('getLastProjectChange')
            ->with($project)
            ->willReturn($lastChange);

        $this->changeCollector
            ->expects($this->once())
            ->method('collectChanges')
            ->willReturn([]);

        $result = $this->manager->processProject($project, [$sub], $now);

        $this->assertSame(0, $result);
    }

    public function testProcessProjectWithMultipleSubscriptions(): void
    {
        $project = new Project();
        $now = new \DateTimeImmutable('2026-07-23 12:15:00');
        $lastChange = new \DateTimeImmutable('2026-07-23 12:00:00');

        $sub1 = new PushSubscription();
        $sub1->setLastChanges(new \DateTimeImmutable('2026-07-23 12:00:00'));

        $sub2 = new PushSubscription();
        $sub2->setLastChanges(new \DateTimeImmutable('2026-07-23 12:05:00'));

        $subscriptions = [$sub1, $sub2];

        $allChanges = [
            [
                'type' => 'task_created',
                'task' => ['id' => 1, 'text' => 'Test task'],
                'page' => ['id' => 1, 'title' => 'Test page'],
                'user' => ['id' => 10, 'name' => 'User A'],
                'createdAt' => new \DateTimeImmutable('2026-07-23 12:10:00'),
            ],
        ];

        $this->changeCollector
            ->expects($this->once())
            ->method('getLastProjectChange')
            ->with($project)
            ->willReturn($lastChange);

        // collectChanges должен быть вызван с минимальным last_changes среди подписок (12:00)
        $this->changeCollector
            ->expects($this->once())
            ->method('collectChanges')
            ->with($project, new \DateTimeImmutable('2026-07-23 12:00:00'), $now)
            ->willReturn($allChanges);

        $this->changeCollector
            ->expects($this->exactly(2))
            ->method('filterChangesSince')
            ->willReturn($allChanges);

        $this->pushService
            ->expects($this->exactly(2))
            ->method('sendBulkNotifications');

        $result = $this->manager->processProject($project, $subscriptions, $now);

        $this->assertSame(2, $result);
    }

    // ==========================================
    // processSubscription tests
    // ==========================================

    public function testProcessSubscriptionWithNewSubscription(): void
    {
        $subscription = new PushSubscription();
        $subscription->setLastChanges(new \DateTimeImmutable('2026-07-23 12:00:00'));
        $now = new \DateTimeImmutable('2026-07-23 12:15:00');

        $changes = [
            [
                'type' => 'task_created',
                'task' => ['id' => 1, 'text' => 'Test task'],
                'page' => ['id' => 1, 'title' => 'Test page'],
                'user' => ['id' => 10, 'name' => 'User A'],
                'createdAt' => new \DateTimeImmutable('2026-07-23 12:10:00'),
            ],
        ];

        $this->changeCollector
            ->expects($this->once())
            ->method('filterChangesSince')
            ->with($changes, new \DateTimeImmutable('2026-07-23 12:00:00'))
            ->willReturn($changes);

        $this->pushService
            ->expects($this->once())
            ->method('sendBulkNotifications')
            ->with([$subscription], $changes, 10);

        $result = $this->manager->processSubscription($subscription, $changes, $now);

        $this->assertSame(1, $result);
        $this->assertEquals($now, $subscription->getLastChanges());
    }

    public function testProcessSubscriptionWithExistingSubscription(): void
    {
        $subscription = new PushSubscription();
        $lastChanges = new \DateTimeImmutable('2026-07-23 12:00:00');
        $subscription->setLastChanges($lastChanges);
        $now = new \DateTimeImmutable('2026-07-23 12:15:00');

        $allChanges = [
            [
                'type' => 'task_created',
                'task' => ['id' => 1, 'text' => 'Old task'],
                'page' => ['id' => 1, 'title' => 'Test page'],
                'user' => ['id' => 10, 'name' => 'User A'],
                'createdAt' => new \DateTimeImmutable('2026-07-23 11:50:00'),
            ],
            [
                'type' => 'task_updated',
                'task' => ['id' => 2, 'text' => 'New task'],
                'page' => ['id' => 1, 'title' => 'Test page'],
                'user' => ['id' => 20, 'name' => 'User B'],
                'createdAt' => new \DateTimeImmutable('2026-07-23 12:10:00'),
            ],
        ];

        $newChanges = [$allChanges[1]];

        $this->changeCollector
            ->expects($this->once())
            ->method('filterChangesSince')
            ->with($allChanges, $lastChanges)
            ->willReturn($newChanges);

        $this->pushService
            ->expects($this->once())
            ->method('sendBulkNotifications')
            ->with([$subscription], $newChanges, 20);

        $result = $this->manager->processSubscription($subscription, $allChanges, $now);

        $this->assertSame(1, $result);
        $this->assertEquals($now, $subscription->getLastChanges());
    }

    public function testProcessSubscriptionNoNewChanges(): void
    {
        $subscription = new PushSubscription();
        $lastChanges = new \DateTimeImmutable('2026-07-23 12:00:00');
        $subscription->setLastChanges($lastChanges);
        $now = new \DateTimeImmutable('2026-07-23 12:15:00');

        $allChanges = [
            [
                'type' => 'task_created',
                'task' => ['id' => 1, 'text' => 'Old task'],
                'page' => ['id' => 1, 'title' => 'Test page'],
                'user' => ['id' => 10, 'name' => 'User A'],
                'createdAt' => new \DateTimeImmutable('2026-07-23 11:50:00'),
            ],
        ];

        $this->changeCollector
            ->expects($this->once())
            ->method('filterChangesSince')
            ->with($allChanges, $lastChanges)
            ->willReturn([]);

        $this->pushService
            ->expects($this->never())
            ->method('sendBulkNotifications');

        $result = $this->manager->processSubscription($subscription, $allChanges, $now);

        $this->assertSame(0, $result);
        $this->assertEquals($now, $subscription->getLastChanges());
    }

    public function testProcessSubscriptionExcludesAuthor(): void
    {
        $subscription = new PushSubscription();
        $subscription->setLastChanges(new \DateTimeImmutable('2026-07-23 12:00:00'));
        $now = new \DateTimeImmutable('2026-07-23 12:15:00');

        $changes = [
            [
                'type' => 'task_created',
                'task' => ['id' => 1, 'text' => 'Test task'],
                'page' => ['id' => 1, 'title' => 'Test page'],
                'user' => ['id' => 10, 'name' => 'User A'],
                'createdAt' => new \DateTimeImmutable('2026-07-23 12:10:00'),
            ],
        ];

        $this->changeCollector
            ->expects($this->once())
            ->method('filterChangesSince')
            ->willReturn($changes);

        $this->pushService
            ->expects($this->once())
            ->method('sendBulkNotifications')
            ->with([$subscription], $changes, 10);

        $result = $this->manager->processSubscription($subscription, $changes, $now);

        $this->assertSame(1, $result);
    }

    public function testProcessSubscriptionWithMultipleAuthors(): void
    {
        $subscription = new PushSubscription();
        $subscription->setLastChanges(new \DateTimeImmutable('2026-07-23 12:00:00'));
        $now = new \DateTimeImmutable('2026-07-23 12:15:00');

        $changes = [
            [
                'type' => 'task_created',
                'task' => ['id' => 1, 'text' => 'Task by A'],
                'page' => ['id' => 1, 'title' => 'Page'],
                'user' => ['id' => 10, 'name' => 'User A'],
                'createdAt' => new \DateTimeImmutable('2026-07-23 12:10:00'),
            ],
            [
                'type' => 'task_updated',
                'task' => ['id' => 2, 'text' => 'Task by B'],
                'page' => ['id' => 1, 'title' => 'Page'],
                'user' => ['id' => 20, 'name' => 'User B'],
                'createdAt' => new \DateTimeImmutable('2026-07-23 12:12:00'),
            ],
        ];

        $this->changeCollector
            ->expects($this->once())
            ->method('filterChangesSince')
            ->willReturn($changes);

        $this->pushService
            ->expects($this->exactly(2))
            ->method('sendBulkNotifications');

        $result = $this->manager->processSubscription($subscription, $changes, $now);

        $this->assertSame(1, $result);
    }

    public function testProcessSubscriptionWithSameAuthorMultipleChanges(): void
    {
        $subscription = new PushSubscription();
        $subscription->setLastChanges(new \DateTimeImmutable('2026-07-23 12:00:00'));
        $now = new \DateTimeImmutable('2026-07-23 12:15:00');

        $changes = [
            [
                'type' => 'task_created',
                'task' => ['id' => 1, 'text' => 'Task 1'],
                'page' => ['id' => 1, 'title' => 'Page'],
                'user' => ['id' => 10, 'name' => 'User A'],
                'createdAt' => new \DateTimeImmutable('2026-07-23 12:10:00'),
            ],
            [
                'type' => 'task_created',
                'task' => ['id' => 2, 'text' => 'Task 2'],
                'page' => ['id' => 1, 'title' => 'Page'],
                'user' => ['id' => 10, 'name' => 'User A'],
                'createdAt' => new \DateTimeImmutable('2026-07-23 12:12:00'),
            ],
        ];

        $this->changeCollector
            ->expects($this->once())
            ->method('filterChangesSince')
            ->willReturn($changes);

        $this->pushService
            ->expects($this->once())
            ->method('sendBulkNotifications')
            ->with([$subscription], $changes, 10);

        $result = $this->manager->processSubscription($subscription, $changes, $now);

        $this->assertSame(1, $result);
    }
}