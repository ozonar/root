<?php

namespace App\Command;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'tasks:fix-cycles',
    description: 'Find and fix cyclic task references (self-referencing parent_id) that break Doctrine topological sort',
)]
class FixTaskCyclesCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Only show cyclic tasks without fixing')
            ->addOption('project-id', null, InputOption::VALUE_OPTIONAL, 'Limit scan to a specific project ID');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $dryRun = (bool) $input->getOption('dry-run');
        $projectId = $input->getOption('project-id');

        /** @var Connection $conn */
        $conn = $this->entityManager->getConnection();

        // 1. Find self-referencing tasks (id = parent_id)
        $selfReferencing = $conn->fetchAllAssociative(
            'SELECT t.id, t.parent_id, t.page_id, t.text, p.project_id
             FROM task t
             JOIN page p ON p.id = t.page_id
             WHERE t.id = t.parent_id'
            . ($projectId ? ' AND p.project_id = :projectId' : ''),
            $projectId ? ['projectId' => $projectId] : []
        );

        if (!empty($selfReferencing)) {
            $io->section('Self-referencing tasks (id = parent_id)');
            $io->table(['id', 'parent_id', 'page_id', 'project_id', 'text'], array_map(fn($r) => [
                $r['id'], $r['parent_id'], $r['page_id'], $r['project_id'], $r['text'],
            ], $selfReferencing));

            if (!$dryRun) {
                $ids = array_column($selfReferencing, 'id');
                $conn->executeStatement(
                    'UPDATE task SET parent_id = NULL WHERE id IN (:ids)',
                    ['ids' => $ids],
                    ['ids' => ArrayParameterType::INTEGER]
                );
                $io->success(sprintf('Fixed %d self-referencing task(s)', count($ids)));
            }
        } else {
            $io->info('No self-referencing tasks found.');
        }

        // 2. Find circular chains using a recursive CTE (PostgreSQL)
        $io->section('Checking for circular chains...');

        try {
            $circular = $conn->fetchAllAssociative(
                'WITH RECURSIVE task_chain AS (
                    SELECT
                        t.id AS start_id,
                        t.id,
                        t.parent_id,
                        1 AS depth,
                        ARRAY[t.id] AS path
                    FROM task t
                    WHERE t.parent_id IS NOT NULL AND t.parent_id != t.id
                    UNION ALL
                    SELECT
                        tc.start_id,
                        t.id,
                        t.parent_id,
                        tc.depth + 1,
                        tc.path || t.id
                    FROM task_chain tc
                    JOIN task t ON t.id = tc.parent_id
                    WHERE tc.depth < 50
                      AND NOT t.id = ANY(tc.path)
                )
                SELECT DISTINCT ON (c.id) c.id, c.parent_id, c.start_id, c.depth, p.project_id
                FROM task_chain c
                JOIN task t ON t.id = c.parent_id
                JOIN page p ON p.id = t.page_id
                WHERE t.id = ANY(c.path)
                   OR t.id = c.start_id'
                . ($projectId ? ' AND p.project_id = :projectId' : '') . '
                ORDER BY c.id',
                $projectId ? ['projectId' => $projectId] : []
            );
        } catch (\Exception $e) {
            $io->warning('Recursive CTE with ARRAY not supported, trying alternative method...');
            $circular = $this->findCircularFallback($conn, $projectId);
        }

        if (!empty($circular)) {
            $io->table(
                ['id', 'parent_id', 'project_id', 'depth'],
                array_map(fn($r) => [$r['id'], $r['parent_id'], $r['project_id'], $r['depth']], $circular)
            );

            if (!$dryRun) {
                $ids = array_column($circular, 'id');
                $conn->executeStatement(
                    'UPDATE task SET parent_id = NULL WHERE id IN (:ids)',
                    ['ids' => $ids],
                    ['ids' => ArrayParameterType::INTEGER]
                );
                $io->success(sprintf('Fixed %d task(s) in circular chains', count($ids)));
            }
        } else {
            $io->info('No circular chains found.');
        }

        // 3. Also find orphaned tasks (parent_id points to non-existent task)
        $orphaned = $conn->fetchAllAssociative(
            'SELECT t.id, t.parent_id, t.page_id, t.text
             FROM task t
             LEFT JOIN task p ON p.id = t.parent_id
             WHERE t.parent_id IS NOT NULL AND p.id IS NULL'
        );

        if (!empty($orphaned)) {
            $io->section('Orphaned tasks (parent_id points to non-existent task)');
            $io->table(['id', 'parent_id', 'page_id', 'text'], array_map(fn($r) => [
                $r['id'], $r['parent_id'], $r['page_id'], $r['text'],
            ], $orphaned));

            if (!$dryRun) {
                $ids = array_column($orphaned, 'id');
                $conn->executeStatement(
                    'UPDATE task SET parent_id = NULL WHERE id IN (:ids)',
                    ['ids' => $ids],
                    ['ids' => ArrayParameterType::INTEGER]
                );
                $io->success(sprintf('Fixed %d orphaned task(s)', count($ids)));
            }
        } else {
            $io->info('No orphaned tasks found.');
        }

        if ($dryRun) {
            $io->warning('Dry-run mode: no changes were made. Run without --dry-run to apply fixes.');
        }

        return Command::SUCCESS;
    }

    /**
     * Fallback method for databases that don't support ARRAY in recursive CTEs.
     * Uses a simpler approach: detect tasks that form a cycle by checking
     * if following parent_id leads back to the starting task within N steps.
     */
    private function findCircularFallback(Connection $conn, ?int $projectId): array
    {
        $allTasks = $conn->fetchAllAssociative(
            'SELECT t.id, t.parent_id' . ($projectId ? ', p.project_id' : '') . '
             FROM task t' . ($projectId ? ' JOIN page p ON p.id = t.page_id' : '') . '
             WHERE t.parent_id IS NOT NULL AND t.parent_id != t.id'
            . ($projectId ? ' AND p.project_id = :projectId' : ''),
            $projectId ? ['projectId' => $projectId] : []
        );

        $adjacency = [];
        foreach ($allTasks as $row) {
            $adjacency[(int) $row['id']] = (int) $row['parent_id'];
        }

        $cyclic = [];
        $visited = [];

        foreach ($adjacency as $startId => $parentId) {
            if (isset($visited[$startId])) {
                continue;
            }

            $path = [];
            $current = $startId;

            while ($current !== null && isset($adjacency[$current])) {
                if (isset($path[$current])) {
                    // Cycle detected! Mark all nodes in the cycle
                    $inCycle = false;
                    foreach ($path as $nodeId => $_) {
                        if ($nodeId === $current) {
                            $inCycle = true;
                        }
                        if ($inCycle) {
                            $cyclic[$nodeId] = true;
                            $visited[$nodeId] = true;
                        }
                    }
                    break;
                }

                $path[$current] = true;
                $current = $adjacency[$current];
            }

            foreach ($path as $nodeId => $_) {
                $visited[$nodeId] = true;
            }
        }

        if (empty($cyclic)) {
            return [];
        }

        $result = $conn->fetchAllAssociative(
            'SELECT t.id, t.parent_id, p.project_id, 0 AS depth
             FROM task t
             JOIN page p ON p.id = t.page_id
             WHERE t.id IN (:ids)',
            ['ids' => array_keys($cyclic)],
            ['ids' => ArrayParameterType::INTEGER]
        );

        return $result;
    }
}