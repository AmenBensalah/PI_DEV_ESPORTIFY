<?php

namespace App\Repository;

use App\Entity\Candidature;
use App\Entity\User;
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

    public function findAcceptedMembershipByUser(User $user): ?Candidature
    {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.equipe', 'e')->addSelect('e')
            ->andWhere('c.user = :user')
            ->andWhere('c.statut LIKE :acceptedPrefix')
            ->setParameter('user', $user)
            ->setParameter('acceptedPrefix', 'Accept%')
            ->orderBy('c.dateCandidature', 'DESC')
            ->addOrderBy('c.id', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
