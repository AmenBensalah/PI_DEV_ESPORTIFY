<?php

namespace App\Controller\Front;

use App\Entity\Produit;
use App\Repository\CategorieRepository;
use App\Repository\ProduitRepository;
use App\Repository\RecommendationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class ProductController extends AbstractController
{
    #[Route('/produits', name: 'app_front_produit_index', methods: ['GET'])]
    #[Route('/front/produit', name: 'app_front_produit_index_alias', methods: ['GET'])]
    public function index(Request $request, ProduitRepository $produitRepository, CategorieRepository $categorieRepository, RecommendationRepository $recommendationRepository): Response
    {
        $search = $request->query->get('q');
        $sort = $request->query->get('sort', 'p.id');
        $dir = $request->query->get('dir', 'ASC');
        $categoryId = $request->query->get('categorie');

        $produits = $produitRepository->searchFront($search, $sort, $dir, $categoryId ? (int)$categoryId : null);

        if ($request->isXmlHttpRequest() || $request->query->get('ajax')) {
            return $this->render('front/produit/_grid.html.twig', [
                'produits' => $produits,
            ]);
        }

        return $this->render('front/produit/index.html.twig', [
            'produits' => $produits,
            'categories' => $categorieRepository->findBy([], ['nom' => 'ASC']),
            'current_search' => $search,
            'current_sort' => $sort,
            'current_dir' => $dir,
            'current_category' => $categoryId,
            'recommendations' => $this->getUser() ? $recommendationRepository->findBy(['user' => $this->getUser()], ['score' => 'DESC'], 4) : [],
        ]);
    }

    #[Route('/produits/{id}', name: 'app_front_produit_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    #[Route('/front/produit/{id}', name: 'app_front_produit_show_alias', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(Produit $produit, ProduitRepository $produitRepository, RecommendationRepository $recommendationRepository, \App\Service\RecommendationService $recommendationService): Response
    {
        // Mock data logic for hardware demo
        if (!$produit->getTechnicalSpecs()) {
            $cat = $produit->getCategorie() ? strtolower($produit->getCategorie()->getNom()) : '';
            if (str_contains(strtolower($produit->getNom()), 'carte mere')) {
                $produit->setTechnicalSpecs("Socket : AM4 / LGA1200\nFormat : ATX / Micro-ATX\nSlots RAM : 4x DDR4\nPort PCIe : 3.0 x16\nGarantie : 2 ans");
                $produit->setInstallDifficulty("Medium");
            } elseif (str_contains(strtolower($produit->getNom()), 'pc gamer')) {
                $produit->setTechnicalSpecs("Processeur : RTX-Ready Intel i9\nGraphismes : NVIDIA GeForce RTX 4070\nStockage : 1To SSD NVMe\nRefroidissement : Watercooling ARGB");
                $produit->setInstallDifficulty("Hard");
            } else {
                $produit->setTechnicalSpecs("Interface : USB 3.0 Gold Plated\nType : Plug & Play\nCompatibilité : Windows / MacOS / Linux\nCâble : 1.8m Tressé");
                $produit->setInstallDifficulty("Easy");
            }
        }

        // 1. Recommandations personnalisées (User-to-Item)
        $userRecs = $this->getUser() ? $recommendationRepository->findBy(['user' => $this->getUser()], ['score' => 'DESC'], 4) : [];

        // 2. Produits achetés ensemble (Item-to-Item IA)
        $relatedProducts = $recommendationService->getFrequentlyBoughtTogether($produit, 3);

        return $this->render('front/produit/show.html.twig', [
            'produit' => $produit,
            'recommendations' => $userRecs,
            'related_products' => $relatedProducts
        ]);
    }
}
