<?php

namespace App\Service;

use App\Entity\Produit;

class ProduitManager
{
    public function validate(Produit $produit): bool
    {
        $nom = trim((string) $produit->getNom());
        if ($nom == '') {
            throw new \InvalidArgumentException('Le nom du produit est obligatoire.');
        }

        $prix = $produit->getPrix();
        if ($prix === null || $prix <= 0) {
            throw new \InvalidArgumentException('Le prix doit etre superieur a zero.');
        }

        $stock = $produit->getStock();
        if ($stock !== null && $stock < 0) {
            throw new \InvalidArgumentException('Le stock ne peut pas etre negatif.');
        }

        return true;
    }
}
