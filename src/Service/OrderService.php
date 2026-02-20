<?php

namespace App\Service;

use App\Entity\Commande;
use App\Entity\LigneCommande;
use App\Entity\Payment;
use App\Entity\Produit;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class OrderService
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
    }

    public function createOrder(?User $user = null): Commande
    {
        $commande = new Commande();
        $commande->setStatut('draft');
        if ($user !== null) {
            $commande->setUser($user);
        }
        
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

    public function ensurePaymentRecordForPaidOrder(Commande $commande): ?Payment
    {
        if ($commande->getStatut() !== 'paid') {
            return null;
        }

        $existingPaidPayment = null;
        foreach ($commande->getPayments() as $payment) {
            $status = mb_strtolower(trim((string) $payment->getStatus()));
            if (str_contains($status, 'paid') || str_contains($status, 'success') || str_contains($status, 'succeeded')) {
                $existingPaidPayment = $payment;
                break;
            }
        }

        $total = $this->recalculateTotal($commande);
        $amount = $total / 100;

        if ($existingPaidPayment instanceof Payment) {
            $existingPaidPayment->setAmount($amount);
            $existingPaidPayment->setStatus('paid');
            return $existingPaidPayment;
        }

        $payment = new Payment();
        $payment->setCommande($commande);
        $payment->setAmount($amount);
        $payment->setStatus('paid');
        $this->entityManager->persist($payment);

        return $payment;
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
