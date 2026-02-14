<?php

namespace App\Command;

use App\Entity\Commande;
use App\Entity\LigneCommande;
use App\Entity\Produit;
use App\Entity\Recommendation;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

#[AsCommand(
    name: 'app:recommendations:generate',
    description: 'Generate product recommendations using Python ML script',
)]
class RecommendationsCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private string $projectDir
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('Starting recommendation engine...');
        
        $mlDir = $this->projectDir . '/var/ml';
        if (!is_dir($mlDir)) {
            mkdir($mlDir, 0777, true);
        }

        // 1. Export Data
        $output->writeln('Exporting data...');
        $lineRepo = $this->entityManager->getRepository(LigneCommande::class);
        $lines = $lineRepo->findAll();
        
        $interactions = [];
        $count = 0;
        
        foreach ($lines as $line) {
            $order = $line->getCommande();
            if (!$order) continue;
            
            $user = $order->getUser();
            if (!$user) continue; // Skip anonymous orders
            
            $produit = $line->getProduit();
            if (!$produit) continue;

            $uId = $user->getId();
            $pId = $produit->getId();
            $qty = $line->getQuantite();
            
            $key = $uId . '_' . $pId;
            if (!isset($interactions[$key])) {
                $interactions[$key] = [
                    'user_id' => $uId, 
                    'product_id' => $pId, 
                    'rating' => 0
                ];
            }
            $interactions[$key]['rating'] += $qty;
            $count++;
        }
        
        $output->writeln(sprintf('Found %d interactions.', count($interactions)));
        
        $inputFile = $mlDir . '/input.json';
        $outputFile = $mlDir . '/output.json';
        
        file_put_contents($inputFile, json_encode(array_values($interactions)));

        // 2. Run Python Script
        $output->writeln('Running Python ML script...');
        $scriptPath = $this->projectDir . '/ml/recommendation.py';
        
        // Try 'python', then 'python3', then 'py'
        $pythonCmd = 'python';
        $process = new Process([$pythonCmd, $scriptPath, $inputFile, $outputFile]);
        $process->run();
        
        if (!$process->isSuccessful()) {
             $output->writeln('Python failed, trying python3...');
             $pythonCmd = 'python3';
             $process = new Process([$pythonCmd, $scriptPath, $inputFile, $outputFile]);
             $process->run();
        }
        
        if (!$process->isSuccessful()) {
             $output->writeln('Python3 failed, trying py...');
             $pythonCmd = 'py';
             $process = new Process([$pythonCmd, $scriptPath, $inputFile, $outputFile]);
             $process->run();
        }

        if (!$process->isSuccessful()) {
            $output->writeln('<error>Error running python script:</error>');
            $output->writeln($process->getErrorOutput());
            $output->writeln('Please ensure python is installed and pandas/scikit-learn are available.');
            return Command::FAILURE;
        }

        // 3. Import Recommendations
        $output->writeln('Importing recommendations...');
        if (!file_exists($outputFile)) {
            $output->writeln('<error>Output file not found</error>');
            return Command::FAILURE;
        }
        
        $json = file_get_contents($outputFile);
        $recommendations = json_decode($json, true);
        
        if (!$recommendations) {
            $output->writeln('No recommendations generated.');
            return Command::SUCCESS;
        }

        $userRepo = $this->entityManager->getRepository(User::class);
        $productRepo = $this->entityManager->getRepository(Produit::class);
        $recRepo = $this->entityManager->getRepository(Recommendation::class);

        // Clear existing recommendations
        // Ideally we should only clear for users in the result set, but simpler to clear all for now
        $this->entityManager->createQuery('DELETE FROM App\Entity\Recommendation')->execute();
        
        $count = 0;
        foreach ($recommendations as $userId => $productIds) {
            $user = $userRepo->find($userId);
            if (!$user) continue;
            
            foreach ($productIds as $idx => $pId) {
                $product = $productRepo->find($pId);
                if (!$product) continue;
                
                $rec = new Recommendation();
                $rec->setUser($user);
                $rec->setProduit($product);
                $rec->setScore((float)(10 - $idx)); // Rank score
                $this->entityManager->persist($rec);
                $count++;
            }
        }
        
        $this->entityManager->flush();
        $output->writeln(sprintf('Saved %d recommendations.', $count));

        return Command::SUCCESS;
    }
}
