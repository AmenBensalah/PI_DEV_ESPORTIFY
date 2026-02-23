<?php

namespace App\Repository;

use App\Entity\Commentaire;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Commentaire>
 */
class CommentaireRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Commentaire::class);
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
        $qb = $this->createQueryBuilder('c')
            ->leftJoin('c.author', 'u')
            ->addSelect('u')
            ->leftJoin('c.post', 'p')
            ->addSelect('p');

        $query = trim((string) ($filters['q'] ?? ''));
        if ($query !== '') {
            $orParts = [
                $qb->expr()->like('LOWER(c.content)', ':query'),
                $qb->expr()->like('LOWER(u.pseudo)', ':query'),
                $qb->expr()->like('LOWER(u.nom)', ':query'),
                $qb->expr()->like('LOWER(u.email)', ':query'),
            ];
            if (ctype_digit($query)) {
                $orParts[] = $qb->expr()->eq('c.id', ':commentId');
                $orParts[] = $qb->expr()->eq('p.id', ':postId');
                $qb->setParameter('commentId', (int) $query)
                    ->setParameter('postId', (int) $query);
            }

            $qb
                ->andWhere($qb->expr()->orX(...$orParts))
                ->setParameter('query', '%' . strtolower($query) . '%');
        }

        $dateFrom = trim((string) ($filters['date_from'] ?? ''));
        if ($dateFrom !== '') {
            $from = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $dateFrom . ' 00:00:00');
            if ($from instanceof \DateTimeImmutable) {
                $qb->andWhere('c.createdAt >= :from')->setParameter('from', $from);
            }
        }

        $dateTo = trim((string) ($filters['date_to'] ?? ''));
        if ($dateTo !== '') {
            $to = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $dateTo . ' 23:59:59');
            if ($to instanceof \DateTimeImmutable) {
                $qb->andWhere('c.createdAt <= :to')->setParameter('to', $to);
            }
        }

        $sort = strtolower(trim((string) ($filters['sort'] ?? 'date')));
        $direction = strtoupper(trim((string) ($filters['direction'] ?? 'DESC'))) === 'ASC' ? 'ASC' : 'DESC';
        $sortMap = [
            'date' => 'c.createdAt',
            'author' => 'u.pseudo',
        ];
        $qb->orderBy($sortMap[$sort] ?? 'c.createdAt', $direction)->addOrderBy('c.id', 'DESC');

        return $qb;
    }

    /**
     * @return string[]
     */
    public function findRecentTextsByAuthor(User $author, int $limit = 30): array
    {
        $rows = $this->createQueryBuilder('c')
            ->select('c.content')
            ->andWhere('c.author = :author')
            ->setParameter('author', $author)
            ->orderBy('c.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getScalarResult();

        return array_values(array_filter(array_map(static fn (array $row) => (string) ($row['content'] ?? ''), $rows)));
    }
}
