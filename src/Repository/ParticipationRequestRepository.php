<?php

namespace App\Repository;

use App\Entity\ParticipationRequest;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ParticipationRequestRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ParticipationRequest::class);
    }

    /**
     * @param array{
     *   tournoi?: string|null,
     *   user?: string|null,
     *   status?: string|null,
     *   sort?: string|null,
     *   order?: string|null
     * } $filters
     *
     * @return ParticipationRequest[]
     */
    public function findForAdminFilters(array $filters): array
    {
        $qb = $this->createQueryBuilder('pr')
            ->leftJoin('pr.tournoi', 't')->addSelect('t')
            ->leftJoin('pr.user', 'u')->addSelect('u');

        $tournoi = trim((string)($filters['tournoi'] ?? ''));
        if ($tournoi !== '') {
            $qb
                ->andWhere('LOWER(t.name) LIKE :tournoi')
                ->setParameter('tournoi', '%' . mb_strtolower($tournoi) . '%');
        }

        $user = trim((string)($filters['user'] ?? ''));
        if ($user !== '') {
            $qb
                ->andWhere('LOWER(COALESCE(u.pseudo, \'\')) LIKE :user OR LOWER(COALESCE(u.email, \'\')) LIKE :user OR LOWER(COALESCE(pr.applicantName, \'\')) LIKE :user OR LOWER(COALESCE(pr.applicantEmail, \'\')) LIKE :user')
                ->setParameter('user', '%' . mb_strtolower($user) . '%');
        }

        $status = trim((string)($filters['status'] ?? ''));
        if ($status !== '') {
            $qb->andWhere('pr.status IN (:statusValues)')
                ->setParameter('statusValues', $this->mapStatusToDbValues($status));
        }

        $allowedSorts = ['createdAt', 'status'];
        $sort = (string)($filters['sort'] ?? 'createdAt');
        $order = strtoupper((string)($filters['order'] ?? 'DESC')) === 'ASC' ? 'ASC' : 'DESC';

        if (!in_array($sort, $allowedSorts, true)) {
            $sort = 'createdAt';
        }

        $qb->orderBy('pr.' . $sort, $order);
        if ($sort !== 'createdAt') {
            $qb->addOrderBy('pr.createdAt', 'DESC');
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @return string[]
     */
    private function mapStatusToDbValues(string $status): array
    {
        return match (mb_strtolower($status)) {
            'pending', 'en_attente' => ['pending', 'en_attente'],
            'approved', 'approuvee', 'approuvée' => ['approved', 'approuvee', 'approuvée'],
            'rejected', 'rejetee', 'rejetée' => ['rejected', 'rejetee', 'rejetée'],
            default => [$status],
        };
    }
}
