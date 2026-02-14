<?php

namespace App\Repository;

use App\Entity\Candidature;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Candidature>
 */
class CandidatureRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Candidature::class);
    }

    public function countAcceptedByEquipeAndRange(\App\Entity\Equipe $equipe, \DateTimeInterface $from, \DateTimeInterface $to): int
    {
        return (int) $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->andWhere('c.equipe = :equipe')
            ->andWhere('c.dateCandidature >= :from')
            ->andWhere('c.dateCandidature < :to')
            ->andWhere('c.statut IN (:accepted)')
            ->setParameter('equipe', $equipe)
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->setParameter('accepted', ['Accepté', 'AcceptÃ©'])
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countTotalByEquipeAndRange(\App\Entity\Equipe $equipe, \DateTimeInterface $from, \DateTimeInterface $to): int
    {
        return (int) $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->andWhere('c.equipe = :equipe')
            ->andWhere('c.dateCandidature >= :from')
            ->andWhere('c.dateCandidature < :to')
            ->setParameter('equipe', $equipe)
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
