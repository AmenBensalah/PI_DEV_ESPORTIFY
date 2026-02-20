<?php

namespace App\Repository;

use App\Entity\Commande;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Commande>
 */
class CommandeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Commande::class);
    }

    //    /**
    //     * @return Commande[] Returns an array of Commande objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('c.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Commande
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }

    /**
     * @return Commande[]
     */
    public function findByNomLike(string $nom): array
    {
        $normalized = mb_strtolower(trim($nom));
        if ($normalized === '') {
            return [];
        }

        return $this->createQueryBuilder('c')
            ->andWhere('LOWER(c.nom) LIKE :nom')
            ->setParameter('nom', '%' . $normalized . '%')
            ->orderBy('c.id', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @return Commande[]
     */
    public function findByProduitNomLike(string $produitNom): array
    {
        $normalized = mb_strtolower(trim($produitNom));
        if ($normalized === '') {
            return [];
        }

        return $this->createQueryBuilder('c')
            ->join('c.lignesCommande', 'lc')
            ->join('lc.produit', 'p')
            ->andWhere('LOWER(p.nom) LIKE :nom')
            ->setParameter('nom', '%' . $normalized . '%')
            ->distinct()
            ->orderBy('c.id', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @return array<string,int> [statut => count]
     */
    public function countByStatut(): array
    {
        $rows = $this->createQueryBuilder('c')
            ->select('c.statut AS statut, COUNT(c.id) AS total')
            ->groupBy('c.statut')
            ->getQuery()
            ->getResult();

        $counts = [];
        foreach ($rows as $row) {
            $counts[(string) $row['statut']] = (int) $row['total'];
        }

        return $counts;
    }

    /**
     * @return array<string,int>
     */
    public function getBehaviorMetricsByPhone(int $numtel, ?int $userId = null, ?string $identityKey = null): array
    {
        $qb = $this->createQueryBuilder('c')
            ->select('COUNT(c.id) AS totalOrders')
            ->addSelect("SUM(CASE WHEN LOWER(c.statut) IN ('pending_payment', 'pending') THEN 1 ELSE 0 END) AS pendingOrders")
            ->addSelect("SUM(CASE WHEN c.statut = 'paid' THEN 1 ELSE 0 END) AS paidOrders")
            ->addSelect("SUM(CASE WHEN c.statut = 'cancelled' THEN 1 ELSE 0 END) AS cancelledOrders")
            ->addSelect("SUM(CASE WHEN c.statut = 'draft' THEN 1 ELSE 0 END) AS draftOrders")
            ->addSelect("COUNT(DISTINCT COALESCE(c.identityKey, CONCAT(COALESCE(c.nom, ''), '|', COALESCE(c.prenom, ''), '|', COALESCE(c.numtel, 0)))) AS identityVariants");

        if ($userId !== null) {
            $or = $qb->expr()->orX(
                'IDENTITY(c.user) = :userId',
                'c.numtel = :numtel'
            );
            if ($identityKey !== null && $identityKey !== '') {
                $or->add('c.identityKey = :identityKey');
                $qb->setParameter('identityKey', $identityKey);
            }
            $qb->andWhere($or)->setParameter('userId', $userId);
        } else {
            $or = $qb->expr()->orX('c.numtel = :numtel');
            if ($identityKey !== null && $identityKey !== '') {
                $or->add('c.identityKey = :identityKey');
                $qb->setParameter('identityKey', $identityKey);
            }
            $qb->andWhere($or);
        }

        $row = $qb
            ->setParameter('numtel', $numtel)
            ->getQuery()
            ->getOneOrNullResult();

        if (!is_array($row)) {
            return [
                'totalOrders' => 0,
                'pendingOrders' => 0,
                'paidOrders' => 0,
                'cancelledOrders' => 0,
                'draftOrders' => 0,
                'identityVariants' => 0,
            ];
        }

        return [
            'totalOrders' => (int) ($row['totalOrders'] ?? 0),
            'pendingOrders' => (int) ($row['pendingOrders'] ?? 0),
            'paidOrders' => (int) ($row['paidOrders'] ?? 0),
            'cancelledOrders' => (int) ($row['cancelledOrders'] ?? 0),
            'draftOrders' => (int) ($row['draftOrders'] ?? 0),
            'identityVariants' => (int) ($row['identityVariants'] ?? 0),
        ];
    }

    /**
     * @return list<array<string,int|string|null>>
     */
    public function getAllPhoneBehaviorMetrics(): array
    {
        $rows = $this->createQueryBuilder('c')
            ->select('c.numtel AS numtel')
            ->addSelect('IDENTITY(c.user) AS userId')
            ->addSelect('c.identityKey AS identityKey')
            ->addSelect('COUNT(c.id) AS totalOrders')
            ->addSelect("SUM(CASE WHEN LOWER(c.statut) IN ('pending_payment', 'pending') THEN 1 ELSE 0 END) AS pendingOrders")
            ->addSelect("SUM(CASE WHEN c.statut = 'paid' THEN 1 ELSE 0 END) AS paidOrders")
            ->addSelect("SUM(CASE WHEN c.statut = 'cancelled' THEN 1 ELSE 0 END) AS cancelledOrders")
            ->addSelect("SUM(CASE WHEN c.statut = 'draft' THEN 1 ELSE 0 END) AS draftOrders")
            ->addSelect("COUNT(DISTINCT COALESCE(c.identityKey, CONCAT(COALESCE(c.nom, ''), '|', COALESCE(c.prenom, ''), '|', COALESCE(c.numtel, 0)))) AS identityVariants")
            ->andWhere('c.numtel IS NOT NULL')
            ->groupBy('c.numtel')
            ->addGroupBy('c.user')
            ->addGroupBy('c.identityKey')
            ->getQuery()
            ->getResult();

        $result = [];
        foreach ($rows as $row) {
            $result[] = [
                'numtel' => (int) ($row['numtel'] ?? 0),
                'userId' => isset($row['userId']) ? (int) $row['userId'] : null,
                'identityKey' => isset($row['identityKey']) ? (string) $row['identityKey'] : null,
                'totalOrders' => (int) ($row['totalOrders'] ?? 0),
                'pendingOrders' => (int) ($row['pendingOrders'] ?? 0),
                'paidOrders' => (int) ($row['paidOrders'] ?? 0),
                'cancelledOrders' => (int) ($row['cancelledOrders'] ?? 0),
                'draftOrders' => (int) ($row['draftOrders'] ?? 0),
                'identityVariants' => (int) ($row['identityVariants'] ?? 0),
            ];
        }

        return $result;
    }

    /**
     * @return array{score: float, block_until: string, message: string}|null
     */
    public function findActiveAiBlockDecision(
        ?int $userId = null,
        ?int $numtel = null,
        ?string $nom = null,
        ?string $prenom = null,
        ?string $identityKey = null
    ): ?array {
        $qb = $this->createQueryBuilder('c')
            ->select('c.aiRiskScore AS score')
            ->addSelect('c.aiBlockUntil AS blockUntil')
            ->addSelect('c.aiBlockReason AS message')
            ->andWhere('c.aiBlocked = true')
            ->andWhere('c.aiBlockUntil IS NOT NULL')
            ->andWhere('c.aiBlockUntil > :now')
            ->setParameter('now', new \DateTimeImmutable());

        $or = $qb->expr()->orX();
        if ($userId !== null) {
            $or->add('IDENTITY(c.user) = :userId');
            $qb->setParameter('userId', $userId);
        }

        if ($numtel !== null && $numtel > 0) {
            $or->add('c.numtel = :numtel');
            $qb->setParameter('numtel', $numtel);
        }

        $normalizedNom = $nom !== null ? mb_strtolower(trim($nom)) : '';
        $normalizedPrenom = $prenom !== null ? mb_strtolower(trim($prenom)) : '';
        if ($normalizedNom !== '' && $normalizedPrenom !== '') {
            $or->add('(LOWER(c.nom) = :nom AND LOWER(c.prenom) = :prenom)');
            $qb->setParameter('nom', $normalizedNom);
            $qb->setParameter('prenom', $normalizedPrenom);
        }

        $normalizedIdentityKey = $identityKey !== null ? trim($identityKey) : '';
        if ($normalizedIdentityKey !== '') {
            $or->add('c.identityKey = :identityKey');
            $qb->setParameter('identityKey', $normalizedIdentityKey);
        }

        if (count($or->getParts()) === 0) {
            return null;
        }

        $row = $qb
            ->andWhere($or)
            ->orderBy('c.aiBlockUntil', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if (!is_array($row) || !isset($row['blockUntil']) || !$row['blockUntil'] instanceof \DateTimeInterface) {
            return null;
        }

        return [
            'score' => (float) ($row['score'] ?? 0.0),
            'block_until' => $row['blockUntil']->format(\DateTimeInterface::ATOM),
            'message' => (string) ($row['message'] ?? ''),
        ];
    }

    /**
     * @return Commande[]
     */
    public function searchAndSort(
        ?string $query,
        ?string $status = null,
        string $sortField = 'id',
        string $sortDirection = 'DESC'
    ): array {
        $qb = $this->createQueryBuilder('c');

        $normalizedQuery = trim((string) $query);
        if ($normalizedQuery !== '') {
            $queryExpr = $qb->expr()->orX(
                $qb->expr()->like('LOWER(c.nom)', ':query'),
                $qb->expr()->like('LOWER(c.prenom)', ':query'),
                $qb->expr()->like('LOWER(c.statut)', ':query'),
                $qb->expr()->like('LOWER(COALESCE(c.aiBlockReason, \'\'))', ':query')
            );

            if (ctype_digit($normalizedQuery)) {
                $queryExpr->add($qb->expr()->eq('c.id', ':queryId'));
                $queryExpr->add($qb->expr()->eq('c.numtel', ':queryNumtel'));
                $qb->setParameter('queryId', (int) $normalizedQuery);
                $qb->setParameter('queryNumtel', (int) $normalizedQuery);
            }

            $qb->andWhere($queryExpr)
                ->setParameter('query', '%' . mb_strtolower($normalizedQuery) . '%');
        }

        $normalizedStatus = mb_strtolower(trim((string) $status));
        if ($normalizedStatus !== '') {
            if ($normalizedStatus === 'blocked_ai') {
                $qb->andWhere('c.aiBlocked = 1');
            } elseif ($normalizedStatus === 'pending') {
                $qb->andWhere('LOWER(c.statut) IN (:pendingStatuses)')
                    ->setParameter('pendingStatuses', ['pending', 'pending_payment']);
            } else {
                $qb->andWhere('LOWER(c.statut) = :status')
                    ->setParameter('status', $normalizedStatus);
            }
        }

        $sortMap = [
            'id' => 'c.id',
            'nom' => 'c.nom',
            'prenom' => 'c.prenom',
            'statut' => 'c.statut',
        ];

        $sortExpression = $sortMap[$sortField] ?? 'c.id';
        $direction = strtoupper($sortDirection) === 'ASC' ? 'ASC' : 'DESC';

        $qb->orderBy($sortExpression, $direction);
        if ($sortExpression !== 'c.id') {
            $qb->addOrderBy('c.id', 'DESC');
        }

        return $qb->getQuery()->getResult();
    }
}
