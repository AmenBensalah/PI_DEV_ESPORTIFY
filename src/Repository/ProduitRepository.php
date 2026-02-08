<?php

namespace App\Repository;

use App\Entity\Produit;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Produit>
 */
class ProduitRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Produit::class);
    }

    public function searchFront(?string $search, ?string $sort = 'p.id', ?string $direction = 'ASC', ?int $categoryId = null): array
    {
        $qb = $this->createQueryBuilder('p')
            ->leftJoin('p.categorie', 'c')
            ->addSelect('c');

        if ($search !== null && $search !== '') {
            $qb->andWhere('p.nom LIKE :search OR p.id = :searchId')
               ->setParameter('search', '%' . $search . '%')
               ->setParameter('searchId', is_numeric($search) ? (int) $search : -1);
        }

        if ($categoryId) {
            $qb->andWhere('c.id = :catId')
               ->setParameter('catId', $categoryId);
        }

        $allowedSorts = ['p.id', 'p.nom', 'p.prix', 'p.stock', 'c.nom'];
        if (!in_array($sort, $allowedSorts)) {
            $sort = 'p.id';
        }
        $direction = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';

        return $qb->orderBy($sort, $direction)->getQuery()->getResult();
    }

    public function searchBack(array $filters): array
    {
        $qb = $this->createQueryBuilder('p')
            ->leftJoin('p.categorie', 'c')
            ->addSelect('c');

        // Text Search (Name)
        if (!empty($filters['q'])) {
            $qb->andWhere('p.nom LIKE :search')
               ->setParameter('search', '%' . $filters['q'] . '%');
        }

        // Min Price
        if (!empty($filters['minPrice'])) {
            $qb->andWhere('p.prix >= :minPrice')
               ->setParameter('minPrice', $filters['minPrice']);
        }

        // Max Price
        if (!empty($filters['maxPrice'])) {
            $qb->andWhere('p.prix <= :maxPrice')
               ->setParameter('maxPrice', $filters['maxPrice']);
        }

        // Category
        if (!empty($filters['categorie'])) {
            $qb->andWhere('c.id = :catId')
               ->setParameter('catId', $filters['categorie']);
        }

        // Status
        if (isset($filters['statut']) && $filters['statut'] !== '') {
            if ($filters['statut'] == '1') {
                 $qb->andWhere("p.statut = 'disponible' OR p.stock > 0");
            } else {
                 $qb->andWhere("p.statut != 'disponible' AND p.stock = 0");
            }
        }

        // Sorting
        $sort = $filters['sort'] ?? 'p.id';
        $direction = 'ASC';

        switch ($sort) {
            case 'prix_asc':
                $sort = 'p.prix';
                $direction = 'ASC';
                break;
            case 'prix_desc':
                $sort = 'p.prix';
                $direction = 'DESC';
                break;
            case 'stock':
                $sort = 'p.stock';
                $direction = 'ASC';
                break;
            default:
                $sort = 'p.id';
                $direction = 'DESC'; // Newest first usually better for admin
        }

        return $qb->orderBy($sort, $direction)->getQuery()->getResult();
    }
}
