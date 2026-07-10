<?php

namespace App\Controller;

use App\Entity\Project;
use App\Entity\Status;
use App\Entity\UserProject;
use App\Entity\Task;
use App\Service\StatusService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/projects')]
class ProjectController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private StatusService $statusService,
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
                    'status' => $task->getStatus()?->getSystemName(),
                    'statusName' => $task->getStatus()?->getName(),
                    'statusIcon' => $task->getStatus()?->getIcon(),
                    'isPriority' => $task->isPriority(),
                    'order' => $task->getOrder(),
                    'parentId' => $task->getParent()?->getId(),
                    'assignee' => $task->getAssignee()?->getEmail(),
                    'assigneeName' => $task->getAssignee()?->getName(),
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
}