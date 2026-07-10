<?php

namespace App\Repository;

use App\Entity\Task;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class TaskRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Task::class);
    }

    /**
     * @return Task[]
     */
    public function findByPage(int $pageId): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.page = :pageId')
            ->setParameter('pageId', $pageId)
            ->orderBy('t.order', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function getMaxOrder(int $pageId): int
    {
        $result = $this->createQueryBuilder('t')
            ->select('MAX(t.order)')
            ->andWhere('t.page = :pageId')
            ->setParameter('pageId', $pageId)
            ->getQuery()
            ->getSingleScalarResult();

        return (int) $result;
    }
}