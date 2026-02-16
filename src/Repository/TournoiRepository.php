<?php

namespace App\Repository;

use App\Entity\Tournoi;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Tournoi>
 */
class TournoiRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Tournoi::class);
    }

    /**
     * @param array{
     *   q?: string|null,
     *   game?: string|null,
     *   type_game?: string|null,
     *   type_tournoi?: string|null,
     *   sort?: string|null,
     *   order?: string|null
     * } $filters
     *
     * @return Tournoi[]
     */
    public function findForAdminFilters(array $filters): array
    {
        $qb = $this->createQueryBuilder('t');

        $q = trim((string)($filters['q'] ?? ''));
        if ($q !== '') {
            $qb
                ->andWhere('LOWER(t.name) LIKE :q OR LOWER(t.game) LIKE :q')
                ->setParameter('q', '%' . mb_strtolower($q) . '%');
        }

        $game = trim((string)($filters['game'] ?? ''));
        if ($game !== '') {
            $qb
                ->andWhere('LOWER(t.game) LIKE :game')
                ->setParameter('game', '%' . mb_strtolower($game) . '%');
        }

        $typeGame = trim((string)($filters['type_game'] ?? ''));
        if ($typeGame !== '') {
            $qb->andWhere('t.type_game = :typeGame')->setParameter('typeGame', $typeGame);
        }

        $typeTournoi = trim((string)($filters['type_tournoi'] ?? ''));
        if ($typeTournoi !== '') {
            $qb->andWhere('t.type_tournoi = :typeTournoi')->setParameter('typeTournoi', $typeTournoi);
        }

        $allowedSorts = ['name', 'startDate'];
        $sort = (string)($filters['sort'] ?? '');
        $order = strtoupper((string)($filters['order'] ?? 'ASC')) === 'DESC' ? 'DESC' : 'ASC';

        if (in_array($sort, $allowedSorts, true)) {
            $qb->orderBy('t.' . $sort, $order);
        }

        return $qb->getQuery()->getResult();
    }

    //    /**
    //     * @return Tournoi[] Returns an array of Tournoi objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('t')
    //            ->andWhere('t.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('t.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Tournoi
    //    {
    //        return $this->createQueryBuilder('t')
    //            ->andWhere('t.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
