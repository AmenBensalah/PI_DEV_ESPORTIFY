<?php

namespace App\Repository;

use App\Entity\Tournoi;
use App\Entity\TournoiMatch;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TournoiMatch>
 */
class TournoiMatchRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TournoiMatch::class);
    }

    /**
     * @return TournoiMatch[]
     */
    public function findByTournoiOrdered(Tournoi $tournoi): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.tournoi = :tournoi')
            ->setParameter('tournoi', $tournoi)
            ->orderBy('m.scheduledAt', 'ASC')
            ->addOrderBy('m.id', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
