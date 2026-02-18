<?php

namespace App\Controller\Front;

use App\Entity\LigneCommande;
use App\Entity\Produit;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Process\Process;

#[Route('/recommendations')]
class RecommendationStatsController extends AbstractController
{
    #[Route('/stats', name: 'app_front_recommendations_stats')]
    public function stats(EntityManagerInterface $entityManager): Response
    {
        $lineRepo = $entityManager->getRepository(LigneCommande::class);
        $productRepo = $entityManager->getRepository(Produit::class);
        
        // 1. Fetch ALL products to ensure "each product" is included
        $allProducts = $productRepo->findAll();
        $productStats = [];
        foreach ($allProducts as $p) {
            $productStats[$p->getId()] = [
                'id' => $p->getId(),
                'name' => $p->getNom(),
                'total_quantity' => 0,
                'unique_buyers' => [],
                'popularity_score' => 0,
                'star_rating' => 0
            ];
        }

        $lines = $lineRepo->findAll();
        $interactions = [];
        $userStats = [];
        
        // 2. Process Command Lines
        foreach ($lines as $line) {
            $order = $line->getCommande();
            if (!$order) continue;
            
            $userId = 'Commande #' . $order->getId();
            
            $produit = $line->getProduit();
            if (!$produit) continue;

            try {
                $pNom = $produit->getNom();
                $pId = $produit->getId();
            } catch (\Doctrine\ORM\EntityNotFoundException $e) {
                continue;
            }
            
            $qty = $line->getQuantite();
            
            // Collect Interactions
            $key = $userId . '_' . $pId;
            if (!isset($interactions[$key])) {
                $interactions[$key] = [
                    'user' => $userId,
                    'product_id' => $pId,
                    'product_name' => $pNom,
                    'quantity' => 0,
                    'order_count' => 0,
                    'star_rating' => 0
                ];
            }
            $interactions[$key]['quantity'] += $qty;
            $interactions[$key]['order_count'] += 1;
            
            // User Profiles
            if (!isset($userStats[$userId])) {
                $userStats[$userId] = [
                    'total_products' => 0,
                    'total_quantity' => 0,
                    'orders' => []
                ];
            }
            $userStats[$userId]['total_products']++;
            $userStats[$userId]['total_quantity'] += $qty;
            if (!in_array($order->getId(), $userStats[$userId]['orders'])) {
                $userStats[$userId]['orders'][] = $order->getId();
            }
            
            // Update Product Stats (if product exists in our pre-fetched list)
            if (isset($productStats[$pId])) {
                $productStats[$pId]['total_quantity'] += $qty;
                if (!in_array($userId, $productStats[$pId]['unique_buyers'])) {
                    $productStats[$pId]['unique_buyers'][] = $userId;
                }
            }
        }
        
        // 3. Calculate Scores and Stars
        $maxQty = 0;
        foreach ($productStats as $stat) {
            if ($stat['total_quantity'] > $maxQty) {
                $maxQty = $stat['total_quantity'];
            }
        }

        foreach ($productStats as $pId => $stat) {
            if ($maxQty > 0 && $stat['total_quantity'] > 0) {
                $score = ($stat['total_quantity'] / $maxQty) * 100;
                $productStats[$pId]['popularity_score'] = round($score);
                
                // New formula: round($score / 20)
                // 100% -> 5 stars
                // 85% -> 4 stars (85/20 = 4.25)
                // 38% -> 2 stars (38/20 = 1.9)
                $stars = round($score / 20);
                $productStats[$pId]['star_rating'] = max(0, min(5, (int)$stars));
            } else {
                $productStats[$pId]['popularity_score'] = 0;
                $productStats[$pId]['star_rating'] = 0;
            }
        }

        // Map product star ratings back to interactions
        foreach ($interactions as $key => $interaction) {
            $pId = $interaction['product_id'];
            if (isset($productStats[$pId])) {
                $interactions[$key]['star_rating'] = $productStats[$pId]['star_rating'];
            }
        }
        
        // Sort products by popularity
        usort($productStats, function($a, $b) {
            return $b['total_quantity'] - $a['total_quantity'];
        });
        
        return $this->render('front/recommendation/stats.html.twig', [
            'interactions' => array_values($interactions),
            'userStats' => $userStats,
            'productStats' => $productStats,
            'totalInteractions' => count($interactions),
            'totalUsers' => count($userStats),
            'totalProducts' => count($allProducts), // All products in catalog
            'maxQty' => $maxQty
        ]);
    }

    #[Route('/test-generator', name: 'app_recommendation_test_gen')]
    public function testGenerator(): JsonResponse
    {
        $pythonPath = 'python';
        $scriptPath = $this->getParameter('kernel.project_dir') . '/ml/item_relations.py';
        $process = new Process([$pythonPath, $scriptPath]);
        $process->run();
        if (!$process->isSuccessful()) {
            return new JsonResponse(['error' => $process->getErrorOutput()], 500);
        }
        return new JsonResponse(['output' => $process->getOutput()]);
    }

    #[Route('/test-item/{id}', name: 'app_recommendation_test_item')]
    public function testItem($id): JsonResponse
    {
        $pythonPath = 'python';
        $scriptPath = $this->getParameter('kernel.project_dir') . '/ml/item_relations.py';
        $fakeId = "Prod_" . ($id % 50 + 1);
        $process = new Process([$pythonPath, $scriptPath, $fakeId]);
        $process->run();
        if (!$process->isSuccessful()) {
            return new JsonResponse(['error' => $process->getErrorOutput()], 500);
        }
        return new JsonResponse(['input' => $fakeId, 'recommendations' => json_decode($process->getOutput())]);
    }
}
