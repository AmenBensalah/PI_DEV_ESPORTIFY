<?php

namespace App\Controller\Front;

use App\Entity\Produit;
use App\Repository\CategorieRepository;
use App\Repository\ProduitRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/produits')]
class ProductController extends AbstractController
{
    #[Route('/', name: 'app_front_produit_index', methods: ['GET'])]
    public function index(Request $request, ProduitRepository $produitRepository, CategorieRepository $categorieRepository): Response
    {
        $search = $request->query->get('q');
        $sort = $request->query->get('sort', 'p.id');
        $dir = $request->query->get('dir', 'ASC');
        $categoryId = $request->query->get('categorie');

        return $this->render('front/produit/index.html.twig', [
            'produits' => $produitRepository->searchFront($search, $sort, $dir, $categoryId ? (int)$categoryId : null),
            'categories' => $categorieRepository->findBy([], ['nom' => 'ASC']),
            'current_search' => $search,
            'current_sort' => $sort,
            'current_dir' => $dir,
            'current_category' => $categoryId,
        ]);
    }

    #[Route('/{id}', name: 'app_front_produit_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(Produit $produit): Response
    {
        return $this->render('front/produit/show.html.twig', [
            'produit' => $produit,
        ]);
    }
}
