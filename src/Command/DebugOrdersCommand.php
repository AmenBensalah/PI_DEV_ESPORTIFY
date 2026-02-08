<?php

namespace App\Command;

use App\Repository\CommandeRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:debug-orders')]
class DebugOrdersCommand extends Command
{
    public function __construct(
        private CommandeRepository $commandeRepository
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $commandes = $this->commandeRepository->findAll();
        $output->writeln('Found ' . count($commandes) . ' orders.');

        foreach ($commandes as $commande) {
            $count = $commande->getLignesCommande()->count();
            $output->writeln(sprintf(
                'Order ID: %d, Status: %s, Item Count: %d',
                $commande->getId(),
                $commande->getStatut(),
                $count
            ));
            if ($count > 0) {
                 foreach ($commande->getLignesCommande() as $line) {
                     $output->writeln(sprintf('   - Product: %s (Qty: %d)', $line->getProduit()->getNom(), $line->getQuantite()));
                 }
            }
        }
        
        return Command::SUCCESS;
    }
}
