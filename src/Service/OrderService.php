<?php

namespace App\Service;

use App\Entity\Commande;
use App\Entity\LigneCommande;
use App\Entity\Produit;
use Doctrine\ORM\EntityManagerInterface;

class OrderService
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
    }

    public function createOrder(): Commande
    {
        $commande = new Commande();
        $commande->setStatut('draft');
        
        $this->entityManager->persist($commande);
        $this->entityManager->flush();

        return $commande;
    }

    public function addProductToOrder(Commande $commande, Produit $produit, int $quantite): void
    {
        if (!$produit->isActive()) {
            throw new \Exception("Product is not active.");
        }

        if ($produit->getStock() < $quantite) {
             throw new \Exception("Not enough stock.");
        }

        // Check if product already in order
        $existingLigne = null;
        foreach ($commande->getLignesCommande() as $ligne) {
            if ($ligne->getProduit() === $produit) {
                $existingLigne = $ligne;
                break;
            }
        }

        if ($existingLigne) {
            $existingLigne->setQuantite($existingLigne->getQuantite() + $quantite);
        } else {
            $ligne = new LigneCommande();
            $ligne->setCommande($commande);
            $ligne->setProduit($produit);
            $ligne->setQuantite($quantite);
            $ligne->setPrix($produit->getPrix()); // Snapshot price
            
            $this->entityManager->persist($ligne);
        }

        $this->entityManager->flush();
        $this->recalculateTotal($commande);
    }

    public function confirmOrder(Commande $commande): void
    {
        if ($commande->getLignesCommande()->isEmpty()) {
            throw new \Exception("Order must have at least one product.");
        }

        if ($commande->getStatut() !== 'draft') {
            throw new \Exception("Order already confirmed.");
        }

        $commande->setStatut('pending_payment');
        $this->entityManager->flush();
    }

    public function updateLineQuantity(Commande $commande, LigneCommande $ligne, int $quantite): void
    {
        if ($quantite <= 0) {
            $commande->removeLigneCommande($ligne);
            $this->entityManager->remove($ligne);
            $this->entityManager->flush();
            $this->recalculateTotal($commande);
            return;
        }

        $produit = $ligne->getProduit();
        if ($produit && $produit->getStock() < $quantite) {
            throw new \Exception("Not enough stock.");
        }

        $ligne->setQuantite($quantite);
        $this->entityManager->flush();
        $this->recalculateTotal($commande);
    }

    public function removeLine(Commande $commande, LigneCommande $ligne): void
    {
        $commande->removeLigneCommande($ligne);
        $this->entityManager->remove($ligne);
        $this->entityManager->flush();
        $this->recalculateTotal($commande);
    }

    public function cancelOrder(Commande $commande): void
    {
        $commande->setStatut('cancelled');
        $this->entityManager->flush();
    }

    public function recalculateTotal(Commande $commande): int
    {
        $total = 0;
        foreach ($commande->getLignesCommande() as $ligne) {
            $total += $ligne->getPrix() * $ligne->getQuantite();
        }
        return $total;
    }
}
