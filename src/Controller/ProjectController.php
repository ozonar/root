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
            $tasks = $this->entityManager
                ->getRepository(Task::class)
                ->findBy(['page' => $page], ['order' => 'ASC']);

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

        // Проверяем, что текущий пользователь — участник проекта
        $currentUser = $this->getUser();
        $isMember = $this->entityManager
            ->getRepository(UserProject::class)
            ->findOneBy(['user' => $currentUser, 'project' => $project]);
        if (!$isMember) {
            return $this->json(['error' => 'Access denied'], Response::HTTP_FORBIDDEN);
        }

        $userProjects = $this->entityManager
            ->getRepository(UserProject::class)
            ->findBy(['project' => $project]);

        $users = [];
        foreach ($userProjects as $up) {
            $user = $up->getUser();
            $userData = [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'name' => $user->getName(),
                'displayName' => $user->getDisplayName(),
            ];

            // If user hasn't completed registration (empty password), include invite info
            if ($user->getPassword() === '' || $user->getPassword() === null) {
                $userData['isInvited'] = true;
                $userData['inviteToken'] = $user->getVerificationToken();
            } else {
                $userData['isInvited'] = false;
            }

            $users[] = $userData;
        }

        return $this->json(['users' => $users]);
    }

    #[Route('/{id}/users/{userId}', name: 'api_project_users_remove', methods: ['DELETE'])]
    public function removeUser(int $id, int $userId, Request $request): JsonResponse
    {
        $project = $this->entityManager->getRepository(Project::class)->find($id);
        if (!$project) {
            return $this->json(['error' => 'Project not found'], Response::HTTP_NOT_FOUND);
        }

        $currentUser = $this->getUser();
        if ($project->getOwner() !== $currentUser) {
            return $this->json(['error' => 'Access denied'], Response::HTTP_FORBIDDEN);
        }

        $user = $this->entityManager->getRepository(User::class)->find($userId);
        if (!$user) {
            return $this->json(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        // Cannot remove the owner
        if ($project->getOwner() === $user) {
            return $this->json(['error' => 'Cannot remove the project owner'], Response::HTTP_BAD_REQUEST);
        }

        $userProject = $this->entityManager
            ->getRepository(UserProject::class)
            ->findOneBy(['user' => $user, 'project' => $project]);

        if (!$userProject) {
            return $this->json(['error' => 'User is not a member of this project'], Response::HTTP_NOT_FOUND);
        }

        $this->entityManager->remove($userProject);
        $this->entityManager->flush();

        return $this->json(['success' => true]);
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
        $name = trim($data['name'] ?? '');

        if ($name === '') {
            return $this->json(['error' => 'Name is required'], Response::HTTP_BAD_REQUEST);
        }

        // Create a new user with invite token
        $user = new User();
        $user->setName($name);
        $user->setEmail('invite-' . uniqid() . '@local');
        $user->setRoles(['ROLE_USER']);

        // Generate invite token
        $inviteToken = $this->tokenService->generateToken();
        $user->setVerificationToken($inviteToken);

        // Set random password (user will replace it during registration)
        $user->setPassword(bin2hex(random_bytes(32)));

        // Set current project so user lands on it after registration
        $user->setCurrentProject($project);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        // Add user to project
        $userProject = new UserProject();
        $userProject->setUser($user);
        $userProject->setProject($project);
        $userProject->setRole('member');

        $this->entityManager->persist($userProject);
        $this->entityManager->flush();

        $inviteLink = $request->getSchemeAndHttpHost() . '/register/invite/' . $inviteToken;

        return $this->json([
            'success' => true,
            'inviteLink' => $inviteLink,
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'name' => $user->getName(),
                'displayName' => $user->getDisplayName(),
            ],
        ]);
    }

    #[Route('', name: 'api_projects_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $name = trim($data['name'] ?? '');

        if ($name === '') {
            return $this->json(['error' => 'Name is required'], Response::HTTP_BAD_REQUEST);
        }

        $user = $this->getUser();

        $project = new Project();
        $project->setName($name);
        $project->setCreatedBy($user);
        $project->setOwner($user);

        $this->entityManager->persist($project);
        $this->entityManager->flush();

        // Add creator as owner member
        $userProject = new UserProject();
        $userProject->setUser($user);
        $userProject->setProject($project);
        $userProject->setRole('owner');

        $this->entityManager->persist($userProject);
        $this->entityManager->flush();

        // Create default statuses
        $this->statusService->createDefaultStatuses($project);

        // Set as current project
        $user->setCurrentProject($project);
        $this->entityManager->flush();

        return $this->json([
            'project' => [
                'id' => $project->getId(),
                'name' => $project->getName(),
                'createdAt' => $project->getCreatedAt()->format('c'),
            ],
        ], Response::HTTP_CREATED);
    }

    #[Route('/{id}/select', name: 'api_project_select', methods: ['PUT'])]
    public function select(int $id): JsonResponse
    {
        $project = $this->entityManager->getRepository(Project::class)->find($id);
        if (!$project) {
            return $this->json(['error' => 'Project not found'], Response::HTTP_NOT_FOUND);
        }

        $user = $this->getUser();
        $user->setCurrentProject($project);
        $this->entityManager->flush();

        return $this->json(['success' => true]);
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

    #[Route('/{id}', name: 'api_project_delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $project = $this->entityManager->getRepository(Project::class)->find($id);
        if (!$project) {
            return $this->json(['error' => 'Project not found'], Response::HTTP_NOT_FOUND);
        }

        $currentUser = $this->getUser();
        if ($project->getOwner() !== $currentUser) {
            return $this->json(['error' => 'Access denied'], Response::HTTP_FORBIDDEN);
        }

        // Remove all user-project associations
        $userProjects = $this->entityManager
            ->getRepository(UserProject::class)
            ->findBy(['project' => $project]);

        foreach ($userProjects as $up) {
            $user = $up->getUser();
            // If this was the user's current project, reset it
            if ($user->getCurrentProject() === $project) {
                $user->setCurrentProject(null);
            }
            $this->entityManager->remove($up);
        }

        // Remove all pages and their tasks
        $pages = $this->entityManager
            ->getRepository(\App\Entity\Page::class)
            ->findBy(['project' => $project]);

        foreach ($pages as $page) {
            // Remove tasks in correct order: first children, then parents
            $tasks = $this->entityManager
                ->getRepository(Task::class)
                ->findBy(['page' => $page]);

            // First pass: collect all task IDs
            $taskIds = array_map(fn(Task $t) => $t->getId(), $tasks);

            // Remove child tasks first (those that have a parent)
            foreach ($tasks as $task) {
                if ($task->getParent() !== null) {
                    $this->entityManager->remove($task);
                }
            }

            // Then remove root tasks (those without a parent)
            foreach ($tasks as $task) {
                if ($task->getParent() === null) {
                    $this->entityManager->remove($task);
                }
            }

            $this->entityManager->remove($page);
        }

        // Remove all statuses
        $statuses = $this->entityManager
            ->getRepository(Status::class)
            ->findBy(['project' => $project]);

        foreach ($statuses as $status) {
            $this->entityManager->remove($status);
        }

        // Finally remove the project itself
        $this->entityManager->remove($project);
        $this->entityManager->flush();

        return $this->json(['success' => true]);
    }
}