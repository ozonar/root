<?php

namespace App\Command;

use App\Entity\User;
use App\Entity\Project;
use App\Entity\UserProject;
use App\Service\StatusService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'user:create',
    description: 'Create a new user with a project and OWNER role',
)]
class CreateUserCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
        private StatusService $statusService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'User email')
            ->addArgument('password', InputArgument::REQUIRED, 'User password')
            ->addArgument('project', InputArgument::REQUIRED, 'Project name to create and assign')
            ->addOption('name', null, InputOption::VALUE_OPTIONAL, 'User display name');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $email = $input->getArgument('email');
        $password = $input->getArgument('password');
        $projectName = $input->getArgument('project');
        $name = $input->getOption('name');

        $existingUser = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
        if ($existingUser) {
            $io->error(sprintf('User with email "%s" already exists.', $email));
            return Command::FAILURE;
        }

        $user = new User();
        $user->setEmail($email);
        $user->setName($name ?? $email);
        $user->setVerified(true);
        $user->setRoles(['ROLE_USER']);

        $hashedPassword = $this->passwordHasher->hashPassword($user, $password);
        $user->setPassword($hashedPassword);

        $this->entityManager->persist($user);

        $project = new Project();
        $project->setName($projectName);
        $project->setCreatedBy($user);
        $project->setOwner($user);

        $this->entityManager->persist($project);

        $userProject = new UserProject();
        $userProject->setUser($user);
        $userProject->setProject($project);
        $userProject->setRole('OWNER');

        $this->entityManager->persist($userProject);

        $user->setCurrentProject($project);

        $this->statusService->createDefaultStatuses($project);

        $this->entityManager->flush();

        $io->success(sprintf(
            'User "%s" created with project "%s" as OWNER.',
            $email,
            $projectName
        ));

        return Command::SUCCESS;
    }
}