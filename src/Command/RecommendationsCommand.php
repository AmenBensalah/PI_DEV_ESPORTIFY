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
        private \App\Service\RecommendationService $recommendationService
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('Starting recommendation engine...');
        
        if ($this->recommendationService->generateRecommendations()) {
            $output->writeln('<info>Recommendations successfully updated!</info>');
            return Command::SUCCESS;
        }

        $output->writeln('<error>Failed to generate recommendations.</error>');
        return Command::FAILURE;
    }
}
