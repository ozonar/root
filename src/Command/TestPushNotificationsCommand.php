<?php

namespace App\Command;

use App\Repository\PushSubscriptionRepository;
use App\Service\PushNotificationService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:test-push-notifications',
    description: 'Send test push notifications to all subscribers',
)]
class TestPushNotificationsCommand extends Command
{
    public function __construct(
        private PushNotificationService $pushService,
        private PushSubscriptionRepository $subscriptionRepo,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('title', 't', InputOption::VALUE_OPTIONAL, 'Notification title', 'Тестовое уведомление')
            ->addOption('message', 'm', InputOption::VALUE_OPTIONAL, 'Notification body', 'Это тестовое push-уведомление от Checker')
            ->addOption('project', 'p', InputOption::VALUE_OPTIONAL, 'Filter by project ID')
            ->addOption('user', 'u', InputOption::VALUE_OPTIONAL, 'Filter by user ID')
            ->addOption('count', 'c', InputOption::VALUE_OPTIONAL, 'Show subscription count and exit', false)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $title = $input->getOption('title');
        $message = $input->getOption('message');
        $projectId = $input->getOption('project');
        $userId = $input->getOption('user');
        $countOnly = $input->getOption('count');

        // Получаем все подписки
        $subscriptions = $this->subscriptionRepo->findAll();

        // Фильтрация по проекту
        if ($projectId !== null) {
            $subscriptions = array_filter($subscriptions, fn($sub) =>
                $sub->getProject()?->getId() === (int) $projectId
            );
        }

        // Фильтрация по пользователю
        if ($userId !== null) {
            $subscriptions = array_filter($subscriptions, fn($sub) =>
                $sub->getUser()?->getId() === (int) $userId
            );
        }

        if ($countOnly !== false) {
            $io->info(sprintf('Total subscriptions: %d', count($subscriptions)));
            return Command::SUCCESS;
        }

        if (empty($subscriptions)) {
            $io->warning('No subscriptions found');
            return Command::SUCCESS;
        }

        $io->info(sprintf('Found %d subscription(s). Sending test notifications...', count($subscriptions)));

        $sent = 0;
        $failed = 0;

        foreach ($subscriptions as $subscription) {
            $userName = $subscription->getUser()?->getDisplayName() ?? 'Unknown';
            $projectName = $subscription->getProject()?->getName() ?? 'Unknown';

            $data = [
                'projectId' => $subscription->getProject()?->getId(),
                'pageIds' => [],
                'test' => true,
            ];

            $success = $this->pushService->sendNotification(
                $subscription,
                $title,
                $message,
                $data,
            );

            if ($success) {
                $io->text(sprintf('  [OK] %s — project "%s"', $userName, $projectName));
                ++$sent;
            } else {
                $io->error(sprintf('  [FAIL] %s — project "%s"', $userName, $projectName));
                ++$failed;
            }
        }

        $io->success(sprintf('Done. Sent: %d, Failed: %d', $sent, $failed));

        return Command::SUCCESS;
    }
}