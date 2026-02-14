<?php

namespace App\Repository;

use App\Entity\Equipe;
use App\Entity\TeamReport;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TeamReport>
 */
class TeamReportRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TeamReport::class);
    }

    public function countRecentByEquipe(Equipe $equipe, \DateTimeInterface $since): int
    {
        return (int) $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->andWhere('r.equipe = :equipe')
            ->andWhere('r.createdAt >= :since')
            ->setParameter('equipe', $equipe)
            ->setParameter('since', $since)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function hasReporterRecent(Equipe $equipe, User $reporter, \DateTimeInterface $since): bool
    {
        $count = (int) $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->andWhere('r.equipe = :equipe')
            ->andWhere('r.reporter = :reporter')
            ->andWhere('r.createdAt >= :since')
            ->setParameter('equipe', $equipe)
            ->setParameter('reporter', $reporter)
            ->setParameter('since', $since)
            ->getQuery()
            ->getSingleScalarResult();

        return $count > 0;
    }

    /**
     * @param int[] $equipeIds
     * @return array<int,int> map equipeId => count
     */
    public function countByEquipeIds(array $equipeIds): array
    {
        if ($equipeIds === []) {
            return [];
        }

        $rows = $this->createQueryBuilder('r')
            ->select('IDENTITY(r.equipe) AS equipe_id, COUNT(r.id) AS total')
            ->andWhere('r.equipe IN (:ids)')
            ->setParameter('ids', $equipeIds)
            ->groupBy('equipe_id')
            ->getQuery()
            ->getArrayResult();

        $map = [];
        foreach ($rows as $row) {
            $map[(int) $row['equipe_id']] = (int) $row['total'];
        }

        return $map;
    }
}
