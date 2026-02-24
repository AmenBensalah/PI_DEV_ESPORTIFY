<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    /**
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function searchAndSortQueryBuilder(?string $query, ?string $roleValue = null, string $sortField = 'id', string $sortDirection = 'DESC'): \Doctrine\ORM\QueryBuilder
    {
        $qb = $this->createQueryBuilder('u');

        if ($query) {
            $qb->andWhere('LOWER(u.pseudo) LIKE LOWER(:query) OR LOWER(u.email) LIKE LOWER(:query) OR LOWER(u.nom) LIKE LOWER(:query)')
               ->setParameter('query', '%' . strtolower($query) . '%');
        }

        if ($roleValue) {
            $roleEnum = \App\Enum\Role::tryFrom($roleValue);
            if ($roleEnum) {
                $qb->andWhere('u.role = :role')
                   ->setParameter('role', $roleEnum);
            }
        }

        // Whitelist sort fields
        $allowedSortFields = ['id', 'nom', 'email'];
        if (!in_array($sortField, $allowedSortFields)) {
            $sortField = 'id';
        }
        
        $sortDirection = strtoupper($sortDirection) === 'ASC' ? 'ASC' : 'DESC';

        return $qb->orderBy('u.' . $sortField, $sortDirection);
    }

    /**
     * @return User[] Returns filtered and sorted users
     */
    public function searchAndSort(?string $query, ?string $roleValue = null, string $sortField = 'id', string $sortDirection = 'DESC'): array
    {
        return $this->searchAndSortQueryBuilder($query, $roleValue, $sortField, $sortDirection)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return User[]
     */
    public function findAdmins(): array
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.role = :role')
            ->setParameter('role', \App\Enum\Role::ADMIN)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return User[]
     */
    public function findUsersWithFaceDescriptor(): array
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.faceDescriptor IS NOT NULL')
            ->getQuery()
            ->getResult();
    }
}
