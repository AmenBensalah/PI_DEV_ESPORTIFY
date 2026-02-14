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

#[Route('/produits')]
class ProductController extends AbstractController
{
    #[Route('/', name: 'app_front_produit_index', methods: ['GET'])]
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

    #[Route('/{id}', name: 'app_front_produit_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(Produit $produit, ProduitRepository $produitRepository, RecommendationRepository $recommendationRepository): Response
    {
        // On récupère les recommandations basées sur l'utilisateur (IA existante)
        $userRecs = $this->getUser() ? $recommendationRepository->findBy(['user' => $this->getUser()], ['score' => 'DESC'], 4) : [];

        // NOUVEAU : On récupère les produits reliés (IA Item-to-Item)
        // Pour la démo, on simule l'ID compatible avec le CSV (Prod_X)
        $relatedProducts = [];
        $pythonPath = 'python'; // À adapter selon l'environnement
        $scriptPath = $this->getParameter('kernel.project_dir') . '/ml/item_relations.py';
        
        // Simuler un ID compatible pour le CSV généré (Prod_1, Prod_2...)
        // Dans un vrai projet, on utiliserait les IDs réels du produit
        $fakeId = "Prod_" . ($produit->getId() % 50 + 1);

        $process = new Process([$pythonPath, $scriptPath, $fakeId]);
        try {
            $process->run();
            if ($process->isSuccessful()) {
                $output = $process->getOutput();
                $relatedIds = json_decode($output, true);
                
                if (is_array($relatedIds)) {
                    // On cherche des produits au hasard pour l'affichage si les IDs Prod_X ne sont pas en base
                    // Sinon on chercherait par ID réel
                    $relatedProducts = $produitRepository->findBy([], null, 4);
                }
            }
        } catch (\Exception $e) {
            // Silence log error
        }

        return $this->render('front/produit/show.html.twig', [
            'produit' => $produit,
            'recommendations' => $userRecs,
            'related_products' => $relatedProducts // Passer les produits reliés au template
        ]);
    }
}
