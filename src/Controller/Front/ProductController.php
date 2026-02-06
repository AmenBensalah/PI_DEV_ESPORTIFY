<?php

namespace App\Controller\Front;

use App\Entity\Produit;
use App\Repository\ProduitRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/produits')]
class ProductController extends AbstractController
{
    #[Route('/', name: 'app_front_produit_index', methods: ['GET'])]
    public function index(\Symfony\Component\HttpFoundation\Request $request, ProduitRepository $produitRepository, \App\Repository\CategorieRepository $categorieRepository): Response
    {
        $search = $request->query->get('q');

        return $this->render('front/produit/index.html.twig', [
            'produits' => $produitRepository->searchFront($search),
            'categories' => $categorieRepository->findAll(),
            'current_search' => $search
        ]);
    }

    #[Route('/{id}', name: 'app_front_produit_show', methods: ['GET'])]
    public function show(Produit $produit): Response
    {
        return $this->render('front/produit/show.html.twig', [
            'produit' => $produit,
        ]);
    }
}
