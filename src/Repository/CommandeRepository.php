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
}
