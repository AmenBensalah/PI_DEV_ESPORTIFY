<?php

namespace App\Repository;

use App\Entity\ChatMessage;
use App\Entity\Equipe;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ChatMessageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ChatMessage::class);
    }

    /**
     * Get recent messages for a team
     */
    public function findRecentByEquipe(Equipe $equipe, int $limit = 50): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.equipe = :equipe')
            ->setParameter('equipe', $equipe)
            ->orderBy('m.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Get unread count for a team
     */
    public function countUnreadByEquipe(Equipe $equipe): int
    {
        return $this->createQueryBuilder('m')
            ->select('COUNT(m.id)')
            ->andWhere('m.equipe = :equipe')
            ->andWhere('m.isRead = :isRead')
            ->setParameter('equipe', $equipe)
            ->setParameter('isRead', false)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Mark all messages as read for a team
     */
    public function markAllAsRead(Equipe $equipe): void
    {
        $this->createQueryBuilder('m')
            ->update()
            ->set('m.isRead', ':isRead')
            ->andWhere('m.equipe = :equipe')
            ->setParameter('isRead', true)
            ->setParameter('equipe', $equipe)
            ->getQuery()
            ->execute();
    }
}
