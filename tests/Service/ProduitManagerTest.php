<?php

namespace App\Tests\Service;

use App\Entity\Produit;
use App\Service\ProduitManager;
use PHPUnit\Framework\TestCase;

class ProduitManagerTest extends TestCase
{
    public function testValidProduit(): void
    {
        $produit = (new Produit())
            ->setNom('Clavier Mecanique')
            ->setPrix(249.99)
            ->setStock(10);

        $manager = new ProduitManager();

        $this->assertTrue($manager->validate($produit));
    }

    public function testProduitWithoutName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le nom du produit est obligatoire.');

        $produit = (new Produit())
            ->setNom('  ')
            ->setPrix(99.99)
            ->setStock(5);

        $manager = new ProduitManager();
        $manager->validate($produit);
    }

    public function testProduitWithInvalidPrice(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le prix doit etre superieur a zero.');

        $produit = (new Produit())
            ->setNom('Souris Pro')
            ->setPrix(0.0)
            ->setStock(5);

        $manager = new ProduitManager();
        $manager->validate($produit);
    }

    public function testProduitWithNegativeStock(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le stock ne peut pas etre negatif.');

        $produit = (new Produit())
            ->setNom('Ecran 240Hz')
            ->setPrix(899.0)
            ->setStock(-3);

        $manager = new ProduitManager();
        $manager->validate($produit);
    }
}
