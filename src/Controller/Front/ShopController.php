<?php

namespace App\Controller\Front;

use App\Repository\ProduitRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ShopController extends AbstractController
{
    #[Route('/', name: 'front_product_list')]
    public function index(ProduitRepository $produitRepository): Response
    {
        return $this->redirectToRoute('app_front_produit_index');
    }
}
