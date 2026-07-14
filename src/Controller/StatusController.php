<?php

namespace App\Controller;

use App\Entity\Project;
use App\Entity\Status;
use App\Service\StatusService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/statuses')]
class StatusController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private StatusService $statusService,
    ) {
    }

    #[Route('', name: 'api_statuses_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $name = $data['name'] ?? null;
        $systemName = $data['systemName'] ?? null;
        $icon = $data['icon'] ?? 'fa-circle';
        $projectId = $data['projectId'] ?? null;

        if (!$name || !$systemName) {
            return $this->json(['error' => 'Name and systemName are required'], Response::HTTP_BAD_REQUEST);
        }

        if (!$projectId) {
            return $this->json(['error' => 'Project are required'], Response::HTTP_BAD_REQUEST);
        }

        $project = $this->entityManager->getRepository(Project::class)->find($projectId);

        if (!$project) {
            return $this->json(['error' => 'Project not found'], Response::HTTP_NOT_FOUND);
        }

        $currentUser = $this->getUser();
        $isMember = $this->entityManager
            ->getRepository(\App\Entity\UserProject::class)
            ->findOneBy(['user' => $currentUser, 'project' => $project]);
        if (!$isMember) {
            return $this->json(['error' => 'Access denied'], Response::HTTP_FORBIDDEN);
        }

        $status = $this->statusService->createStatus($systemName, $name, $icon, $project);

        return $this->json([
            'status' => [
                'id' => $status->getId(),
                'systemName' => $status->getSystemName(),
                'name' => $status->getName(),
                'icon' => $status->getIcon(),
            ],
        ], Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'api_statuses_update', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $status = $this->entityManager->getRepository(Status::class)->find($id);
        if (!$status) {
            return $this->json(['error' => 'Status not found'], Response::HTTP_NOT_FOUND);
        }

        $project = $status->getProject();
        if ($project) {
            $currentUser = $this->getUser();
            $isMember = $this->entityManager
                ->getRepository(\App\Entity\UserProject::class)
                ->findOneBy(['user' => $currentUser, 'project' => $project]);
            if (!$isMember) {
                return $this->json(['error' => 'Access denied'], Response::HTTP_FORBIDDEN);
            }
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['name'])) {
            $name = trim($data['name']);
            if ($name === '') {
                return $this->json(['error' => 'Name is required'], Response::HTTP_BAD_REQUEST);
            }
            $status->setName($name);
        }

        if (isset($data['icon'])) {
            $status->setIcon($data['icon']);
        }

        if (isset($data['systemName'])) {
            $status->setSystemName($data['systemName']);
        }

        $this->entityManager->flush();

        return $this->json([
            'status' => [
                'id' => $status->getId(),
                'systemName' => $status->getSystemName(),
                'name' => $status->getName(),
                'icon' => $status->getIcon(),
            ],
        ]);
    }

    #[Route('/{id}', name: 'api_statuses_delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $status = $this->entityManager->getRepository(Status::class)->find($id);
        if (!$status) {
            return $this->json(['error' => 'Status not found'], Response::HTTP_NOT_FOUND);
        }

        $project = $status->getProject();
        if ($project) {
            $currentUser = $this->getUser();
            $isMember = $this->entityManager
                ->getRepository(\App\Entity\UserProject::class)
                ->findOneBy(['user' => $currentUser, 'project' => $project]);
            if (!$isMember) {
                return $this->json(['error' => 'Access denied'], Response::HTTP_FORBIDDEN);
            }
        }

        // Don't allow deleting default statuses
        if (in_array($status->getSystemName(), ['processed', 'finished'])) {
            return $this->json(['error' => 'Cannot delete default statuses'], Response::HTTP_BAD_REQUEST);
        }

        // Set tasks with this status to processed
        foreach ($status->getTasks() as $task) {
            $processedStatus = $this->entityManager
                ->getRepository(Status::class)
                ->findOneBy(['systemName' => 'processed', 'project' => $project]);
            if ($processedStatus) {
                $task->setStatus($processedStatus);
            }
        }

        $this->entityManager->remove($status);
        $this->entityManager->flush();

        return $this->json(['success' => true]);
    }
}