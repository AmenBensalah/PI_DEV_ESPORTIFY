<?php

namespace App\Service;

use App\Entity\LigneCommande;
use App\Entity\Produit;
use App\Entity\Recommendation;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Process\Process;

class RecommendationService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private string $projectDir
    ) {
    }

    /**
     * Generates recommendations based on current command lines and orders.
     */
    public function generateRecommendations(): bool
    {
        $mlDir = $this->projectDir . '/var/ml';
        $inputFile = $this->exportData($mlDir);
        $outputFile = $mlDir . '/output.json';
        
        // 2. Run Python Script
        $scriptPath = $this->projectDir . '/ml/recommendation.py';
        
        $process = new Process(['python', $scriptPath, $inputFile, $outputFile]);
        $process->run();
        
        if (!$process->isSuccessful()) {
             $process = new Process(['python3', $scriptPath, $inputFile, $outputFile]);
             $process->run();
        }
        
        if (!$process->isSuccessful()) {
            return false;
        }

        // 3. Import Recommendations
        if (!file_exists($outputFile)) {
            return false;
        }
        
        $json = file_get_contents($outputFile);
        $recommendations = json_decode($json, true);
        
        if (!$recommendations) {
            return true;
        }

        $userRepo = $this->entityManager->getRepository(User::class);
        $productRepo = $this->entityManager->getRepository(Produit::class);

        // Clear existing recommendations for users present in results
        $this->entityManager->createQuery('DELETE FROM App\Entity\Recommendation')->execute();
        
        foreach ($recommendations as $userId => $productIds) {
            if (strpos($userId, 'guest_') === 0) continue;

            $user = $userRepo->find((int)$userId);
            if (!$user) continue;
            
            foreach ($productIds as $idx => $pId) {
                $product = $productRepo->find($pId);
                if (!$product) continue;
                
                $rec = new Recommendation();
                $rec->setUser($user);
                $rec->setProduit($product);
                $rec->setScore((float)(10 - $idx)); // Rank score
                $this->entityManager->persist($rec);
            }
        }
        
        $this->entityManager->flush();
        return true;
    }

    /**
     * Returns products Frequently Bought Together with the given product.
     */
    public function getFrequentlyBoughtTogether(Produit $produit, int $limit = 3): array
    {
        $mlDir = $this->projectDir . '/var/ml';
        $inputFile = $this->exportData($mlDir);
        $outputFile = $mlDir . '/item_relations_output.json';
        
        $scriptPath = $this->projectDir . '/ml/item_relations.py';
        
        $process = new Process(['python', $scriptPath, $inputFile, (string)$produit->getId(), $outputFile]);
        $process->run();
        
        if (!$process->isSuccessful()) {
            $process = new Process(['python3', $scriptPath, $inputFile, (string)$produit->getId(), $outputFile]);
            $process->run();
        }

        if (!$process->isSuccessful() || !file_exists($outputFile)) {
            return [];
        }

        $relatedIds = json_decode(file_get_contents($outputFile), true);
        if (!is_array($relatedIds)) return [];

        $relatedProducts = [];
        $productRepo = $this->entityManager->getRepository(Produit::class);
        foreach (array_slice($relatedIds, 0, $limit) as $id) {
            $p = $productRepo->find($id);
            if ($p) $relatedProducts[] = $p;
        }

        return $relatedProducts;
    }

    private function exportData(string $mlDir): string
    {
        if (!is_dir($mlDir)) {
            mkdir($mlDir, 0777, true);
        }

        $lineRepo = $this->entityManager->getRepository(LigneCommande::class);
        $lines = $lineRepo->findAll();
        
        $interactions = [];
        foreach ($lines as $line) {
            try {
                $order = $line->getCommande();
                if (!$order) continue;
                $order->getStatut(); 
                $userId = 'guest_' . $order->getId();
                
                $p = $line->getProduit();
                if (!$p) continue;
                $p->getNom();
                $pId = $p->getId();
                
                $qty = $line->getQuantite();
                $key = $userId . '_' . $pId;
                if (!isset($interactions[$key])) {
                    $interactions[$key] = ['user_id' => $userId, 'product_id' => $pId, 'rating' => 0];
                }
                $interactions[$key]['rating'] += $qty;
            } catch (\Exception $e) { continue; }
        }
        
        $inputFile = $mlDir . '/input.json';
        file_put_contents($inputFile, json_encode(array_values($interactions)));
        return $inputFile;
    }
}
