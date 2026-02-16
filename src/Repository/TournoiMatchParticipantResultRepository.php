<?php

namespace App\Repository;

use App\Entity\TournoiMatch;
use App\Entity\TournoiMatchParticipantResult;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TournoiMatchParticipantResult>
 */
class TournoiMatchParticipantResultRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TournoiMatchParticipantResult::class);
    }

    public function findOneByMatchAndParticipant(TournoiMatch $match, User $participant): ?TournoiMatchParticipantResult
    {
        return $this->findOneBy([
            'match' => $match,
            'participant' => $participant,
        ]);
    }
}

