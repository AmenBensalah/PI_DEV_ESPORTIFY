<?php

namespace App\Controller\Front;

use App\Entity\LigneCommande;
use App\Entity\Commande;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Process\Process;
use Symfony\Component\HttpFoundation\JsonResponse;

#[Route('/recommendations')]
class RecommendationStatsController extends AbstractController
{
    #[Route('/stats', name: 'app_front_recommendations_stats')]
    public function stats(EntityManagerInterface $entityManager): Response
    {
        // Collecter les données d'interactions utilisateur-produit
        $lineRepo = $entityManager->getRepository(LigneCommande::class);
        $lines = $lineRepo->findAll();
        
        $interactions = [];
        $userStats = [];
        $productStats = [];
        
        foreach ($lines as $line) {
            $order = $line->getCommande();
            if (!$order) continue;
            
            // Pour l'instant, sans user_id, on utilise l'ID de commande
            $userId = 'Commande #' . $order->getId();
            
            $produit = $line->getProduit();
            if (!$produit) continue;

            $pId = $produit->getId();
            $pNom = $produit->getNom();
            $qty = $line->getQuantite();
            
            $key = $userId . '_' . $pId;
            if (!isset($interactions[$key])) {
                $interactions[$key] = [
                    'user' => $userId,
                    'product_id' => $pId,
                    'product_name' => $pNom,
                    'quantity' => 0,
                    'order_count' => 0
                ];
            }
            $interactions[$key]['quantity'] += $qty;
            $interactions[$key]['order_count'] += 1;
            
            // Stats par utilisateur
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
            
            // Stats par produit
            if (!isset($productStats[$pId])) {
                $productStats[$pId] = [
                    'name' => $pNom,
                    'total_quantity' => 0,
                    'unique_buyers' => []
                ];
            }
            $productStats[$pId]['total_quantity'] += $qty;
            if (!in_array($userId, $productStats[$pId]['unique_buyers'])) {
                $productStats[$pId]['unique_buyers'][] = $userId;
            }
        }
        
        // Trier par popularité
        usort($productStats, function($a, $b) {
            return count($b['unique_buyers']) - count($a['unique_buyers']);
        });
        
        return $this->render('front/recommendation/stats.html.twig', [
            'interactions' => array_values($interactions),
            'userStats' => $userStats,
            'productStats' => $productStats,
            'totalInteractions' => count($interactions),
            'totalUsers' => count($userStats),
            'totalProducts' => count($productStats)
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
