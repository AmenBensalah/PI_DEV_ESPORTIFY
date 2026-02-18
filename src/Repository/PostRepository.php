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
     * Returns all posts ordered by createdAt DESC, with authors and comment authors eagerly loaded.
     * Uses two separate queries to avoid cartesian product duplicates from multiple collection joins.
     * This prevents EntityNotFoundException when a post's author or a comment's author has been deleted.
     *
     * @return Post[]
     */
    public function findAllWithAuthor(): array
    {
        // Query 1: load posts + post authors (ManyToOne — safe to join)
        $posts = $this->createQueryBuilder('p')
            ->leftJoin('p.author', 'a')
            ->addSelect('a')
            ->orderBy('p.createdAt', 'DESC')
            ->addOrderBy('p.id', 'DESC')
            ->getQuery()
            ->getResult();

        if (empty($posts)) {
            return [];
        }

        // Query 2: load all comments + their authors for the fetched posts in one go.
        // This hydrates the already-tracked Post entities' commentaires collections
        // and eagerly loads each comment's author, preventing lazy-load on deleted users.
        $postIds = array_map(static fn(Post $p) => $p->getId(), $posts);

        $this->getEntityManager()
            ->createQueryBuilder()
            ->select('c', 'ca')
            ->from(\App\Entity\Commentaire::class, 'c')
            ->leftJoin('c.author', 'ca')
            ->where('IDENTITY(c.post) IN (:ids)')
            ->setParameter('ids', $postIds)
            ->orderBy('c.createdAt', 'ASC')
            ->getQuery()
            ->getResult();

        return $posts;
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
