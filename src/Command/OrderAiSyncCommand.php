<?php

namespace App\Command;

use App\Entity\Commande;
use App\Repository\CommandeRepository;
use App\Service\UnpaidOrderAbuseDetectionService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:order-ai:sync',
    description: 'Synchronize AI abuse fields on draft/pending orders.',
)]
class OrderAiSyncCommand extends Command
{
    public function __construct(
        private CommandeRepository $commandeRepository,
        private EntityManagerInterface $entityManager,
        private UnpaidOrderAbuseDetectionService $abuseDetectionService
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $orders = $this->commandeRepository->createQueryBuilder('c')
            ->andWhere('c.statut IN (:statuses)')
            ->setParameter('statuses', ['draft', 'pending_payment'])
            ->orderBy('c.id', 'DESC')
            ->getQuery()
            ->getResult();

        $blockedCount = 0;
        $clearedCount = 0;
        $skippedCount = 0;

        foreach ($orders as $commande) {
            if (!$commande instanceof Commande) {
                continue;
            }

            $nom = trim((string) ($commande->getNom() ?? ''));
            $prenom = trim((string) ($commande->getPrenom() ?? ''));
            $numtel = $commande->getNumtel();

            if ($nom === '' || $prenom === '' || $numtel === null || $numtel <= 0) {
                if ($commande->isAiBlocked() || $commande->getAiRiskScore() !== null || $commande->getAiBlockReason() !== null) {
                    $commande->setAiBlocked(false);
                    $commande->setAiRiskScore(null);
                    $commande->setAiBlockReason(null);
                    $commande->setAiBlockedAt(null);
                    $commande->setAiBlockUntil(null);
                    $clearedCount++;
                } else {
                    $skippedCount++;
                }
                continue;
            }

            $decision = $this->abuseDetectionService->assessAndMaybeBlock(
                $nom,
                $prenom,
                (int) $numtel,
                $commande->getUser()?->getId()
            );

            if (($decision['blocked'] ?? false) === true) {
                $commande->setAiBlocked(true);
                $commande->setAiRiskScore(isset($decision['score']) ? (float) $decision['score'] : null);
                $commande->setAiBlockReason(isset($decision['message']) ? (string) $decision['message'] : null);
                $commande->setAiBlockedAt(new \DateTimeImmutable());

                $blockUntilRaw = isset($decision['block_until']) ? (string) $decision['block_until'] : '';
                if ($blockUntilRaw !== '') {
                    try {
                        $commande->setAiBlockUntil(new \DateTimeImmutable($blockUntilRaw));
                    } catch (\Throwable) {
                        $commande->setAiBlockUntil(null);
                    }
                } else {
                    $commande->setAiBlockUntil(null);
                }

                $blockedCount++;
            } else {
                if ($commande->isAiBlocked() || $commande->getAiRiskScore() !== null || $commande->getAiBlockReason() !== null) {
                    $commande->setAiBlocked(false);
                    $commande->setAiRiskScore(null);
                    $commande->setAiBlockReason(null);
                    $commande->setAiBlockedAt(null);
                    $commande->setAiBlockUntil(null);
                    $clearedCount++;
                }
            }
        }

        $this->entityManager->flush();

        $output->writeln(sprintf('Orders scanned: %d', count($orders)));
        $output->writeln(sprintf('AI blocked set: %d', $blockedCount));
        $output->writeln(sprintf('AI cleared: %d', $clearedCount));
        $output->writeln(sprintf('Skipped: %d', $skippedCount));

        return Command::SUCCESS;
    }
}
