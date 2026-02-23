<?php

namespace App\Repository;

use App\Entity\FeedAiAnalysis;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<FeedAiAnalysis>
 */
class FeedAiAnalysisRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FeedAiAnalysis::class);
    }

    public function findOneForEntity(string $entityType, int $entityId): ?FeedAiAnalysis
    {
        return $this->findOneBy([
            'entityType' => $entityType,
            'entityId' => $entityId,
        ]);
    }

    /**
     * @param int[] $entityIds
     * @return array<int, FeedAiAnalysis>
     */
    public function findMapForEntities(string $entityType, array $entityIds): array
    {
        $entityIds = array_values(array_unique(array_filter($entityIds, static fn ($id) => is_int($id) || ctype_digit((string) $id))));
        if ($entityIds === []) {
            return [];
        }

        $rows = $this->createQueryBuilder('a')
            ->andWhere('a.entityType = :entityType')
            ->andWhere('a.entityId IN (:entityIds)')
            ->setParameter('entityType', $entityType)
            ->setParameter('entityIds', $entityIds)
            ->getQuery()
            ->getResult();

        $map = [];
        foreach ($rows as $row) {
            $map[$row->getEntityId()] = $row;
        }

        return $map;
    }

    /**
     * @return FeedAiAnalysis[]
     */
    public function findFlagged(string $entityType, int $limit = 100): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.entityType = :entityType')
            ->andWhere('a.autoAction != :allow OR a.toxicityScore >= 65 OR a.spamScore >= 70 OR a.hateSpeechScore >= 60')
            ->setParameter('entityType', $entityType)
            ->setParameter('allow', 'allow')
            ->orderBy('a.updatedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function countFlagged(string $entityType): int
    {
        return (int) $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->andWhere('a.entityType = :entityType')
            ->andWhere('a.autoAction != :allow OR a.toxicityScore >= 65 OR a.spamScore >= 70 OR a.hateSpeechScore >= 60')
            ->setParameter('entityType', $entityType)
            ->setParameter('allow', 'allow')
            ->getQuery()
            ->getSingleScalarResult();
    }
}
