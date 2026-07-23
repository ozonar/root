<?php

namespace App\Command;

use Minishlink\WebPush\VAPID;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:generate-vapid-keys',
    description: 'Generate VAPID keys for Web Push notifications',
)]
class GenerateVapidKeysCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'Admin email for VAPID subject')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $email = $input->getArgument('email');
        $subject = 'mailto:' . $email;

        $io->title('VAPID Key Generator');

        $keys = VAPID::createVapidKeys();

        $io->section('Add these to your .env file:');
        $io->writeln('');
        $io->writeln('VAPID_PUBLIC_KEY=' . $keys['publicKey']);
        $io->writeln('VAPID_PRIVATE_KEY=' . $keys['privateKey']);
        $io->writeln('VAPID_SUBJECT=' . $subject);
        $io->writeln('');

        $io->success('Copy the keys above into your .env file.');

        return Command::SUCCESS;
    }
}