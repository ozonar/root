<?php

namespace App\Controller;

use App\Entity\Project;
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
}