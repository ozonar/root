<?php

namespace App\Repository;

use App\Entity\Page;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class PageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Page::class);
    }

    /**
     * @return Page[]
     */
    public function findRecentByProject(int $projectId, int $limit = 10): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.project = :projectId')
            ->andWhere('p.deletedAt IS NULL')
            ->setParameter('projectId', $projectId)
            ->orderBy('p.editedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}