<?php

namespace App\Repository;

use App\Entity\Announcement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Announcement>
 */
class AnnouncementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Announcement::class);
    }

    /**
     * @param array{
     *     q?: string,
     *     date_from?: string,
     *     date_to?: string,
     *     sort?: string,
     *     direction?: string
     * } $filters
     */
    public function searchAdmin(array $filters): array
    {
        return $this->searchAdminQueryBuilder($filters)->getQuery()->getResult();
    }

    /**
     * @param array{
     *     q?: string,
     *     date_from?: string,
     *     date_to?: string,
     *     sort?: string,
     *     direction?: string
     * } $filters
     */
    public function searchAdminQueryBuilder(array $filters): \Doctrine\ORM\QueryBuilder
    {
        $qb = $this->createQueryBuilder('a');

        $query = trim((string) ($filters['q'] ?? ''));
        if ($query !== '') {
            $orParts = [
                $qb->expr()->like('LOWER(a.title)', ':query'),
                $qb->expr()->like('LOWER(a.tag)', ':query'),
                $qb->expr()->like('LOWER(a.link)', ':query'),
                $qb->expr()->like('LOWER(a.content)', ':query'),
            ];
            if (ctype_digit($query)) {
                $orParts[] = $qb->expr()->eq('a.id', ':announcementId');
                $qb->setParameter('announcementId', (int) $query);
            }

            $qb
                ->andWhere($qb->expr()->orX(...$orParts))
                ->setParameter('query', '%' . strtolower($query) . '%');
        }

        $dateFrom = trim((string) ($filters['date_from'] ?? ''));
        if ($dateFrom !== '') {
            $from = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $dateFrom . ' 00:00:00');
            if ($from instanceof \DateTimeImmutable) {
                $qb->andWhere('a.createdAt >= :from')->setParameter('from', $from);
            }
        }

        $dateTo = trim((string) ($filters['date_to'] ?? ''));
        if ($dateTo !== '') {
            $to = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $dateTo . ' 23:59:59');
            if ($to instanceof \DateTimeImmutable) {
                $qb->andWhere('a.createdAt <= :to')->setParameter('to', $to);
            }
        }

        $sort = strtolower(trim((string) ($filters['sort'] ?? 'date')));
        $direction = strtoupper(trim((string) ($filters['direction'] ?? 'DESC'))) === 'ASC' ? 'ASC' : 'DESC';
        $sortMap = [
            'date' => 'a.createdAt',
            'title' => 'a.title',
        ];
        $qb->orderBy($sortMap[$sort] ?? 'a.createdAt', $direction)->addOrderBy('a.id', 'DESC');

        return $qb;
    }
}
