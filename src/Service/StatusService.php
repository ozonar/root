<?php

namespace App\Service;

use App\Entity\Status;
use App\Entity\User;
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

    public function createDefaultStatuses(?User $user = null): void
    {
        foreach (self::DEFAULT_STATUSES as $statusData) {
            $existing = $this->entityManager
                ->getRepository(Status::class)
                ->findOneBy(['systemName' => $statusData['systemName']]);

            if ($existing) {
                continue;
            }

            $status = new Status();
            $status->setSystemName($statusData['systemName']);
            $status->setName($statusData['name']);
            $status->setIcon($statusData['icon']);
            $status->setUser($user);
            $status->setActive(true);

            $this->entityManager->persist($status);
        }

        $this->entityManager->flush();
    }
}