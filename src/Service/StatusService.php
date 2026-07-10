<?php

namespace App\Service;

use App\Entity\Status;
use App\Entity\Project;
use Doctrine\ORM\EntityManagerInterface;

class StatusService
{
    private EntityManagerInterface $entityManager;

    private const DEFAULT_STATUSES = [
        ['systemName' => 'finished', 'name' => 'Finished', 'icon' => 'fa-check-circle'],
        ['systemName' => 'processed', 'name' => 'Processed', 'icon' => 'fa-cogs'],
    ];

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function createStatus(string $systemName, string $name, string $icon, ?Project $project = null): Status
    {
        $status = new Status();
        $status->setSystemName($systemName);
        $status->setName($name);
        $status->setIcon($icon);
        $status->setProject($project);
        $status->setActive(true);

        $this->entityManager->persist($status);
        $this->entityManager->flush();

        return $status;
    }

    public function createDefaultStatuses(?Project $project = null): void
    {
        foreach (self::DEFAULT_STATUSES as $statusData) {
            $existing = $this->entityManager
                ->getRepository(Status::class)
                ->findOneBy(['systemName' => $statusData['systemName'], 'project' => $project]);

            if ($existing) {
                continue;
            }

            $status = new Status();
            $status->setSystemName($statusData['systemName']);
            $status->setName($statusData['name']);
            $status->setIcon($statusData['icon']);
            $status->setProject($project);
            $status->setActive(true);

            $this->entityManager->persist($status);
        }

        $this->entityManager->flush();
    }
}