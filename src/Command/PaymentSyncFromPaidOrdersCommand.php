<?php

namespace App\Command;

use App\Entity\Commande;
use App\Repository\CommandeRepository;
use App\Service\OrderService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:payment:sync-paid-orders',
    description: 'Create missing payment records for orders already marked as paid.',
)]
class PaymentSyncFromPaidOrdersCommand extends Command
{
    public function __construct(
        private CommandeRepository $commandeRepository,
        private OrderService $orderService,
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $orders = $this->commandeRepository->findBy(['statut' => 'paid'], ['id' => 'ASC']);
        $synced = 0;
        $updated = 0;

        foreach ($orders as $order) {
            if (!$order instanceof Commande) {
                continue;
            }

            $beforeCount = $order->getPayments()->count();
            $payment = $this->orderService->ensurePaymentRecordForPaidOrder($order);
            if ($payment === null) {
                continue;
            }

            if ($order->getPayments()->count() > $beforeCount) {
                $synced++;
            } else {
                $updated++;
            }
        }

        $this->entityManager->flush();

        $output->writeln(sprintf('Paid orders scanned: %d', count($orders)));
        $output->writeln(sprintf('Missing payment rows created: %d', $synced));
        $output->writeln(sprintf('Existing payment rows updated: %d', $updated));

        return Command::SUCCESS;
    }
}
