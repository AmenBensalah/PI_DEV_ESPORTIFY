<?php

namespace App\Repository;

use App\Entity\Post;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Post>
 */
class PostRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Post::class);
    }

    /**
     * @param array{
     *     q?: string,
     *     media?: string,
     *     date_from?: string,
     *     date_to?: string,
     *     sort?: string,
     *     direction?: string
     * } $filters
     */
    public function searchAdmin(array $filters): array
    {
        $qb = $this->createQueryBuilder('p');

        $query = trim((string) ($filters['q'] ?? ''));
        if ($query !== '') {
            $orParts = [
                $qb->expr()->like('LOWER(p.content)', ':query'),
                $qb->expr()->like('LOWER(p.imagePath)', ':query'),
                $qb->expr()->like('LOWER(p.videoUrl)', ':query'),
                $qb->expr()->like('LOWER(p.eventTitle)', ':query'),
            ];
            if (ctype_digit($query)) {
                $orParts[] = $qb->expr()->eq('p.id', ':postId');
                $qb->setParameter('postId', (int) $query);
            }

            $qb
                ->andWhere($qb->expr()->orX(...$orParts))
                ->setParameter('query', '%' . strtolower($query) . '%');
        }

        $media = strtolower(trim((string) ($filters['media'] ?? '')));
        if ($media === 'event') {
            $qb->andWhere('p.isEvent = :isEvent')->setParameter('isEvent', true);
        } elseif ($media === 'post') {
            $qb->andWhere('p.isEvent = :isEvent')->setParameter('isEvent', false);
        }

        $dateFrom = trim((string) ($filters['date_from'] ?? ''));
        if ($dateFrom !== '') {
            $from = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $dateFrom . ' 00:00:00');
            if ($from instanceof \DateTimeImmutable) {
                $qb->andWhere('p.createdAt >= :from')->setParameter('from', $from);
            }
        }

        $dateTo = trim((string) ($filters['date_to'] ?? ''));
        if ($dateTo !== '') {
            $to = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $dateTo . ' 23:59:59');
            if ($to instanceof \DateTimeImmutable) {
                $qb->andWhere('p.createdAt <= :to')->setParameter('to', $to);
            }
        }

        $sort = strtolower(trim((string) ($filters['sort'] ?? 'date')));
        $direction = strtoupper(trim((string) ($filters['direction'] ?? 'DESC'))) === 'ASC' ? 'ASC' : 'DESC';
        $sortMap = [
            'date' => 'p.createdAt',
            'type' => 'p.isEvent',
        ];
        $qb->orderBy($sortMap[$sort] ?? 'p.createdAt', $direction)->addOrderBy('p.id', 'DESC');

        return $qb->getQuery()->getResult();
    }

    /**
     * @return Post[]
     */
    public function findRecentByAuthorWithMedias(User $author, int $limit = 20): array
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.medias', 'm')->addSelect('m')
            ->andWhere('p.author = :author')
            ->setParameter('author', $author)
            ->orderBy('p.createdAt', 'DESC')
            ->addOrderBy('p.id', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    //    /**
    //     * @return Post[] Returns an array of Post objects
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

    //    public function findOneBySomeField($value): ?Post
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
