<?php

namespace App\Controller;

use App\Entity\Project;
use App\Entity\Status;
use App\Entity\User;
use App\Entity\UserProject;
use App\Entity\Task;
use App\Service\Mailer\MailerService;
use App\Service\StatusService;
use App\Service\TokenService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/projects')]
class ProjectController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private StatusService $statusService,
        private MailerService $mailerService,
        private TokenService $tokenService,
        private UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    #[Route('', name: 'api_projects_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $user = $this->getUser();
        $userProjects = $this->entityManager
            ->getRepository(UserProject::class)
            ->findBy(['user' => $user]);

        $projects = [];
        foreach ($userProjects as $up) {
            $project = $up->getProject();
            $projects[] = [
                'id' => $project->getId(),
                'name' => $project->getName(),
                'role' => $up->getRole(),
                'createdAt' => $project->getCreatedAt()->format('c'),
            ];
        }

        return $this->json([
            'projects' => $projects,
            'currentProjectId' => $user->getCurrentProject()?->getId(),
        ]);
    }

    #[Route('/{id}/pages', name: 'api_project_pages', methods: ['GET'])]
    public function pages(int $id): JsonResponse
    {
        $project = $this->entityManager->getRepository(Project::class)->find($id);
        if (!$project) {
            return $this->json(['error' => 'Project not found'], Response::HTTP_NOT_FOUND);
        }

        $pages = $this->entityManager
            ->getRepository(\App\Entity\Page::class)
            ->findBy(['project' => $project], ['editedAt' => 'DESC']);

        $result = [];
        foreach ($pages as $page) {
            // Get first 10 tasks for preview
            $tasks = $this->entityManager
                ->getRepository(Task::class)
                ->findBy(['page' => $page], ['order' => 'ASC'], 10);

            $tasksResult = [];
            foreach ($tasks as $task) {
                $tasksResult[] = [
                    'id' => $task->getId(),
                    'text' => $task->getText(),
                    'description' => $task->getDescription(),
                    'status' => $task->getStatus()?->getSystemName(),
                    'statusName' => $task->getStatus()?->getName(),
                    'statusIcon' => $task->getStatus()?->getIcon(),
                    'isPriority' => $task->isPriority(),
                    'order' => $task->getOrder(),
                    'parentId' => $task->getParent()?->getId(),
                    'assignee' => $task->getAssignee()?->getEmail(),
                    'assigneeName' => $task->getAssignee()?->getDisplayName(),
                ];
            }

            $result[] = [
                'id' => $page->getId(),
                'title' => $page->getTitle(),
                'editedAt' => $page->getEditedAt()?->format('c'),
                'createdAt' => $page->getCreatedAt()->format('c'),
                'tasks' => $tasksResult,
                'taskCount' => $this->entityManager
                    ->getRepository(Task::class)
                    ->count(['page' => $page]),
            ];
        }

        return $this->json(['pages' => $result]);
    }

    #[Route('/{id}/users', name: 'api_project_users', methods: ['GET'])]
    public function users(int $id): JsonResponse
    {
        $project = $this->entityManager->getRepository(Project::class)->find($id);
        if (!$project) {
            return $this->json(['error' => 'Project not found'], Response::HTTP_NOT_FOUND);
        }

        // Проверяем, что текущий пользователь — владелец проекта
        $currentUser = $this->getUser();
        if ($project->getOwner() !== $currentUser) {
            return $this->json(['error' => 'Access denied'], Response::HTTP_FORBIDDEN);
        }

        $userProjects = $this->entityManager
            ->getRepository(UserProject::class)
            ->findBy(['project' => $project]);

        $users = [];
        foreach ($userProjects as $up) {
            $user = $up->getUser();
            $users[] = [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'name' => $user->getName(),
                'displayName' => $user->getDisplayName(),
            ];
        }

        return $this->json(['users' => $users]);
    }

    #[Route('/{id}/statuses', name: 'api_project_statuses', methods: ['GET'])]
    public function statuses(int $id): JsonResponse
    {
        $project = $this->entityManager->getRepository(Project::class)->find($id);
        if (!$project) {
            return $this->json(['error' => 'Project not found'], Response::HTTP_NOT_FOUND);
        }

        $statuses = $this->entityManager
            ->getRepository(Status::class)
            ->findBy(['project' => $project]);

        $result = [];
        foreach ($statuses as $status) {
            $result[] = [
                'id' => $status->getId(),
                'systemName' => $status->getSystemName(),
                'name' => $status->getName(),
                'icon' => $status->getIcon(),
            ];
        }

        return $this->json(['statuses' => $result]);
    }

    #[Route('/{id}/pages', name: 'api_project_pages_create', methods: ['POST'])]
    public function createPage(int $id, Request $request): JsonResponse
    {
        $project = $this->entityManager->getRepository(Project::class)->find($id);
        if (!$project) {
            return $this->json(['error' => 'Project not found'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);
        $title = $data['title'] ?? 'Новая страница';

        $page = new \App\Entity\Page();
        $page->setTitle($title);
        $page->setProject($project);
        $page->setUser($this->getUser());

        $this->entityManager->persist($page);
        $this->entityManager->flush();

        return $this->json([
            'page' => [
                'id' => $page->getId(),
                'title' => $page->getTitle(),
                'createdAt' => $page->getCreatedAt()->format('c'),
            ],
        ], Response::HTTP_CREATED);
    }

    #[Route('/{id}/invite', name: 'api_project_invite', methods: ['POST'])]
    public function invite(int $id, Request $request): JsonResponse
    {
        $project = $this->entityManager->getRepository(Project::class)->find($id);
        if (!$project) {
            return $this->json(['error' => 'Project not found'], Response::HTTP_NOT_FOUND);
        }

        $currentUser = $this->getUser();
        if ($project->getOwner() !== $currentUser) {
            return $this->json(['error' => 'Access denied'], Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), true);
        $email = trim($data['email'] ?? '');

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->json(['error' => 'Invalid email format'], Response::HTTP_BAD_REQUEST);
        }

        // Check if user exists
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);

        if (!$user) {
            // Create a new user with a temporary token
            $user = new User();
            $user->setEmail($email);
            $user->setRoles(['ROLE_USER']);

            $tempPassword = bin2hex(random_bytes(8));
            $hashedPassword = $this->passwordHasher->hashPassword($user, $tempPassword);
            $user->setPassword($hashedPassword);

            $verificationToken = $this->tokenService->generateToken();
            $user->setVerificationToken($verificationToken);

            $this->entityManager->persist($user);
            $this->entityManager->flush();

            // Send invitation email with temp password
            $subject = 'Приглашение в проект "' . $project->getName() . '"';
            $body = sprintf(
                "Здравствуйте!\n\nВас пригласили в проект \"%s\" в системе Checker.\n\nВаш временный пароль: %s\n\nПожалуйста, войдите в систему и смените пароль.\n\nСсылка для входа: %s\n\nС уважением,\nКоманда Checker",
                $project->getName(),
                $tempPassword,
                $request->getSchemeAndHttpHost() . '/login'
            );
            $this->mailerService->send($email, $subject, $body);
        }

        // Check if already in project
        $existingUP = $this->entityManager
            ->getRepository(UserProject::class)
            ->findOneBy(['user' => $user, 'project' => $project]);

        if (!$existingUP) {
            $userProject = new UserProject();
            $userProject->setUser($user);
            $userProject->setProject($project);
            $userProject->setRole('member');

            $this->entityManager->persist($userProject);
            $this->entityManager->flush();

            // If user already existed, send notification
            if ($user->isVerified()) {
                $subject = 'Приглашение в проект "' . $project->getName() . '"';
                $body = sprintf(
                    "Здравствуйте!\n\nВас пригласили в проект \"%s\" в системе Checker.\n\nСсылка для входа: %s\n\nС уважением,\nКоманда Checker",
                    $project->getName(),
                    $request->getSchemeAndHttpHost() . '/login'
                );
                $this->mailerService->send($email, $subject, $body);
            }
        }

        return $this->json([
            'success' => true,
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'name' => $user->getName(),
                'displayName' => $user->getDisplayName(),
            ],
        ]);
    }

    #[Route('/{id}/rename', name: 'api_project_rename', methods: ['PUT'])]
    public function rename(int $id, Request $request): JsonResponse
    {
        $project = $this->entityManager->getRepository(Project::class)->find($id);
        if (!$project) {
            return $this->json(['error' => 'Project not found'], Response::HTTP_NOT_FOUND);
        }

        $currentUser = $this->getUser();
        if ($project->getOwner() !== $currentUser) {
            return $this->json(['error' => 'Access denied'], Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), true);
        $name = trim($data['name'] ?? '');

        if ($name === '') {
            return $this->json(['error' => 'Name is required'], Response::HTTP_BAD_REQUEST);
        }

        $project->setName($name);
        $this->entityManager->flush();

        return $this->json([
            'success' => true,
            'project' => [
                'id' => $project->getId(),
                'name' => $project->getName(),
            ],
        ]);
    }
}