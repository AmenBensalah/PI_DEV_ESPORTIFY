<?php

namespace App\Repository;

use App\Entity\Equipe;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Equipe>
 */
class EquipeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Equipe::class);
    }

    /**
     * @return Equipe[] Returns an array of Equipe objects matching the search term
     */
    public function searchByName(string $term): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('LOWER(e.nomEquipe) LIKE LOWER(:term)')
            ->setParameter('term', '%'.strtolower($term).'%')
            ->orderBy('e.nomEquipe', 'ASC')
            ->setMaxResults(20)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Equipe[] Returns filtered and sorted teams
     */
    public function searchAndSort(?string $query, ?string $region = null, ?string $visibility = null, string $sortField = 'id', string $sortDirection = 'DESC'): array
    {
        $qb = $this->createQueryBuilder('e');

        if ($query) {
            $qb->andWhere('LOWER(e.nomEquipe) LIKE LOWER(:query) OR LOWER(e.tag) LIKE LOWER(:query)')
               ->setParameter('query', '%' . strtolower($query) . '%');
        }

        if ($region) {
            $qb->andWhere('e.region = :region')
               ->setParameter('region', $region);
        }

        if ($visibility !== null && $visibility !== '') {
            $isPrivate = $visibility === 'private';
            $qb->andWhere('e.isPrivate = :isPrivate')
               ->setParameter('isPrivate', $isPrivate);
        }

        // Whitelist sort fields to prevent SQL injection
        $allowedSortFields = ['id', 'nomEquipe', 'tag', 'dateCreation', 'classement'];
        if (!in_array($sortField, $allowedSortFields)) {
            $sortField = 'id';
        }
        
        // Whitelist direction
        $sortDirection = strtoupper($sortDirection) === 'ASC' ? 'ASC' : 'DESC';

        return $qb->orderBy('e.' . $sortField, $sortDirection)
                  ->getQuery()
                  ->getResult();
    }

//    /**
//     * @return Equipe[] Returns an array of Equipe objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('e')
//            ->andWhere('e.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('e.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Equipe
//    {
//        return $this->createQueryBuilder('e')
//            ->andWhere('e.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
