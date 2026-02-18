<?php

namespace App\Controller\Front;

use App\Repository\CategorieRepository;
use App\Repository\ProduitRepository;
use App\Service\OrderService;
use App\Service\RecommendationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/bundle')]
class BundleController extends AbstractController
{
    #[Route('/builder', name: 'front_bundle_builder')]
    public function builder(ProduitRepository $produitRepository, CategorieRepository $categorieRepository): Response
    {
        // Define the steps for the bundle
        // Step 1: Souris, Step 2: Clavier, Step 3: Casque (ou Audio)
        $steps = [
            ['name' => 'Souris', 'icon' => 'fas fa-mouse', 'search' => 'souris'],
            ['name' => 'Clavier', 'icon' => 'fas fa-keyboard', 'search' => 'clavier'],
            ['name' => 'Casque / Audio', 'icon' => 'fas fa-headphones', 'search' => 'audio'],
        ];

        $selection = [];
        foreach ($steps as $step) {
            // Find products in category or by name search
            $selection[$step['name']] = $produitRepository->createQueryBuilder('p')
                ->where('p.nom LIKE :q')
                ->andWhere('p.stock > 0')
                ->setParameter('q', '%' . $step['search'] . '%')
                ->setMaxResults(8)
                ->getQuery()
                ->getResult();
        }

        return $this->render('front/bundle/builder.html.twig', [
            'steps' => $steps,
            'selection' => $selection
        ]);
    }

    #[Route('/add-to-cart', name: 'front_bundle_add_to_cart', methods: ['POST'])]
    public function addBundleToCart(Request $request, OrderService $orderService, ProduitRepository $produitRepository, EntityManagerInterface $entityManager, RecommendationService $recommendationService): Response
    {
        $productIds = $request->request->all('products');
        if (empty($productIds)) {
            $this->addFlash('error', 'Aucun produit sélectionné.');
            return $this->redirectToRoute('front_bundle_builder');
        }

        $session = $request->getSession();
        $orderId = $session->get('current_order_id');
        $commande = null;

        if ($orderId) {
            $commande = $entityManager->getRepository(\App\Entity\Commande::class)->find($orderId);
        }

        if (!$commande) {
            $commande = $orderService->createOrder();
            $entityManager->flush();
            $session->set('current_order_id', $commande->getId());
        }

        foreach ($productIds as $pId) {
            $produit = $produitRepository->find($pId);
            if ($produit) {
                $orderService->addProductToOrder($commande, $produit, 1);
            }
        }

        $entityManager->flush();
        
        // Trigger recommendations update
        $recommendationService->generateRecommendations();

        $this->addFlash('success', 'Votre pack personnalisé a été ajouté au panier !');
        return $this->redirectToRoute('front_order_cart');
    }
}
