<?php

namespace App\Repository;

use App\Entity\Payment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Payment>
 */
class PaymentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Payment::class);
    }

//    /**
//     * @return Payment[] Returns an array of Payment objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('p.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Payment
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }

    /**
     * @return Payment[]
     */
    public function searchAndSort(
        ?string $query,
        ?string $status = null,
        string $sortField = 'id',
        string $sortDirection = 'DESC'
    ): array {
        $qb = $this->createQueryBuilder('p')
            ->leftJoin('p.commande', 'c')
            ->addSelect('c');

        $normalizedQuery = trim((string) $query);
        if ($normalizedQuery !== '') {
            $queryExpr = $qb->expr()->orX(
                $qb->expr()->like('LOWER(p.status)', ':query')
            );

            if (ctype_digit($normalizedQuery)) {
                $queryExpr->add($qb->expr()->eq('p.id', ':queryInt'));
                $queryExpr->add($qb->expr()->eq('c.id', ':queryCommandeId'));

                $qb->setParameter('queryInt', (int) $normalizedQuery);
                $qb->setParameter('queryCommandeId', (int) $normalizedQuery);
            }

            if (is_numeric($normalizedQuery)) {
                $queryExpr->add($qb->expr()->eq('p.amount', ':queryAmount'));
                $qb->setParameter('queryAmount', (float) $normalizedQuery);
            }

            $qb->andWhere($queryExpr)
                ->setParameter('query', '%' . mb_strtolower($normalizedQuery) . '%');
        }

        $normalizedStatus = mb_strtolower(trim((string) $status));
        if ($normalizedStatus !== '') {
            if ($normalizedStatus === 'succeeded') {
                $qb->andWhere($qb->expr()->orX(
                    $qb->expr()->like('LOWER(p.status)', ':statusSuccess'),
                    $qb->expr()->like('LOWER(p.status)', ':statusSucceeded'),
                    $qb->expr()->like('LOWER(p.status)', ':statusPaid')
                ));
                $qb->setParameter('statusSuccess', '%success%');
                $qb->setParameter('statusSucceeded', '%succeeded%');
                $qb->setParameter('statusPaid', '%paid%');
            } elseif ($normalizedStatus === 'failed') {
                $qb->andWhere($qb->expr()->orX(
                    $qb->expr()->like('LOWER(p.status)', ':statusFailed'),
                    $qb->expr()->like('LOWER(p.status)', ':statusCancelled')
                ));
                $qb->setParameter('statusFailed', '%failed%');
                $qb->setParameter('statusCancelled', '%cancel%');
            } elseif ($normalizedStatus === 'pending') {
                $qb->andWhere('LOWER(p.status) LIKE :statusPending')
                    ->setParameter('statusPending', '%pending%');
            } else {
                $qb->andWhere('LOWER(p.status) = :status')
                    ->setParameter('status', $normalizedStatus);
            }
        }

        $sortMap = [
            'id' => 'p.id',
            'montant' => 'p.amount',
            'date' => 'p.createdAt',
            'status' => 'p.status',
            'commande' => 'c.id',
        ];

        $sortExpression = $sortMap[$sortField] ?? 'p.id';
        $direction = strtoupper($sortDirection) === 'ASC' ? 'ASC' : 'DESC';

        $qb->orderBy($sortExpression, $direction);
        if ($sortExpression !== 'p.id') {
            $qb->addOrderBy('p.id', 'DESC');
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @return list<array{amount: float, status: string, createdAt: \DateTimeImmutable}>
     */
    public function getEventsSince(\DateTimeImmutable $from): array
    {
        $rows = $this->createQueryBuilder('p')
            ->select('p.amount AS amount, p.status AS status, p.createdAt AS createdAt')
            ->andWhere('p.createdAt >= :from')
            ->setParameter('from', $from)
            ->orderBy('p.createdAt', 'ASC')
            ->getQuery()
            ->getArrayResult();

        $result = [];
        foreach ($rows as $row) {
            $createdAtRaw = $row['createdAt'] ?? null;
            if ($createdAtRaw instanceof \DateTimeImmutable) {
                $createdAt = $createdAtRaw;
            } elseif ($createdAtRaw instanceof \DateTimeInterface) {
                $createdAt = \DateTimeImmutable::createFromInterface($createdAtRaw);
            } elseif (is_string($createdAtRaw) && $createdAtRaw !== '') {
                try {
                    $createdAt = new \DateTimeImmutable($createdAtRaw);
                } catch (\Throwable) {
                    continue;
                }
            } else {
                continue;
            }

            $result[] = [
                'amount' => (float) ($row['amount'] ?? 0.0),
                'status' => mb_strtolower(trim((string) ($row['status'] ?? ''))),
                'createdAt' => $createdAt,
            ];
        }

        return $result;
    }
}
