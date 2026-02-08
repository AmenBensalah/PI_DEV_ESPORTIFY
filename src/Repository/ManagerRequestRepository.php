<?php

namespace App\Repository;

use App\Entity\ManagerRequest;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ManagerRequest>
 *
 * @method ManagerRequest|null find($id, $lockMode = null, $lockVersion = null)
 * @method ManagerRequest|null findOneBy(array $criteria, array $orderBy = null)
 * @method ManagerRequest[]    findAll()
 * @method ManagerRequest[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ManagerRequestRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ManagerRequest::class);
    }

    public function save(ManagerRequest $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(ManagerRequest $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
    /**
     * @return ManagerRequest[]
     */
    public function searchAndSort(?string $query, ?string $status = 'pending', string $sortField = 'id', string $sortDirection = 'DESC'): array
    {
        $qb = $this->createQueryBuilder('mr')
            ->leftJoin('mr.user', 'u');

        if ($query) {
            $qb->andWhere('LOWER(u.pseudo) LIKE LOWER(:query) OR LOWER(u.email) LIKE LOWER(:query) OR LOWER(u.nom) LIKE LOWER(:query) OR LOWER(mr.motivation) LIKE LOWER(:query)')
               ->setParameter('query', '%' . strtolower($query) . '%');
        }

        if ($status) {
            $qb->andWhere('mr.status = :status')
               ->setParameter('status', $status);
        }

        // Whitelist sort fields
        $allowedSortFields = ['id', 'createdAt', 'status'];
        if (!in_array($sortField, $allowedSortFields)) {
            $sortField = 'id';
        }
        
        $sortDirection = strtoupper($sortDirection) === 'ASC' ? 'ASC' : 'DESC';

        return $qb->orderBy('mr.' . $sortField, $sortDirection)
                  ->getQuery()
                  ->getResult();
    }
}
