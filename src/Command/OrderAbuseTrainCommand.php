<?php

namespace App\Command;

use App\Repository\CommandeRepository;
use App\Service\OrderAbuseMLService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Process\Process;

#[AsCommand(
    name: 'app:order-abuse:train',
    description: 'Train the unpaid-order abuse ML model from current order history.',
)]
class OrderAbuseTrainCommand extends Command
{
    public function __construct(
        private CommandeRepository $commandeRepository,
        private OrderAbuseMLService $orderAbuseMLService,
        private KernelInterface $kernel
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $rows = $this->commandeRepository->getAllPhoneBehaviorMetrics();
        if ($rows === []) {
            $output->writeln('<comment>No phone-level behavior data found. Nothing to train.</comment>');
            return Command::SUCCESS;
        }

        $datasetRows = [];
        foreach ($rows as $row) {
            $total = max(1, (int) ($row['totalOrders'] ?? 0));
            $pending = max(0, (int) ($row['pendingOrders'] ?? 0));
            $paid = max(0, (int) ($row['paidOrders'] ?? 0));
            $cancelled = max(0, (int) ($row['cancelledOrders'] ?? 0));
            $draft = max(0, (int) ($row['draftOrders'] ?? 0));
            $variants = max(0, (int) ($row['identityVariants'] ?? 0));

            $datasetRows[] = [
                'user_id' => isset($row['userId']) ? (int) $row['userId'] : 0,
                'numtel' => (int) ($row['numtel'] ?? 0),
                'has_user_account' => isset($row['userId']) && $row['userId'] !== null ? 1 : 0,
                'total_orders' => $total,
                'pending_orders' => $pending,
                'paid_orders' => $paid,
                'cancelled_orders' => $cancelled,
                'draft_orders' => $draft,
                'identity_variants' => $variants,
                'unpaid_ratio' => round(($pending + $cancelled) / $total, 6),
                // Weak supervision label from historical behavior.
                'label_abuse' => ($pending >= 4 && $paid === 0) || (($pending + $cancelled) >= 5 && $paid <= 1) ? 1 : 0,
            ];
        }

        $datasetPath = $this->orderAbuseMLService->datasetPath();
        $workDir = dirname($datasetPath);
        if (!is_dir($workDir) && !mkdir($workDir, 0777, true) && !is_dir($workDir)) {
            $output->writeln('<error>Unable to create var/order_abuse_ai directory.</error>');
            return Command::FAILURE;
        }

        $headers = [
            'user_id',
            'numtel',
            'has_user_account',
            'total_orders',
            'pending_orders',
            'paid_orders',
            'cancelled_orders',
            'draft_orders',
            'identity_variants',
            'unpaid_ratio',
            'label_abuse',
        ];
        $this->writeCsv($datasetPath, $datasetRows, $headers);

        $scriptPath = $this->kernel->getProjectDir() . '/ml/order_abuse_train.py';
        if (!is_file($scriptPath)) {
            $output->writeln('<error>Python trainer not found: ml/order_abuse_train.py</error>');
            return Command::FAILURE;
        }

        $modelPath = $this->orderAbuseMLService->modelPath();
        $metadataPath = $this->orderAbuseMLService->metadataPath();

        $pythonBinaries = ['python', 'python3', 'py'];
        $lastErrorOutput = '';
        $success = false;

        foreach ($pythonBinaries as $python) {
            $process = new Process([$python, $scriptPath, $datasetPath, $modelPath, $metadataPath]);
            $process->setTimeout(180);
            $process->run();

            if ($process->isSuccessful()) {
                $success = true;
                $output->writeln(sprintf('<info>Training completed with %s.</info>', $python));
                if (trim($process->getOutput()) !== '') {
                    $output->writeln(trim($process->getOutput()));
                }
                break;
            }

            $lastErrorOutput = trim($process->getErrorOutput() . "\n" . $process->getOutput());
        }

        if (!$success) {
            $output->writeln('<error>Unable to run Python trainer. Install Python + pandas + scikit-learn + joblib.</error>');
            if ($lastErrorOutput !== '') {
                $output->writeln($lastErrorOutput);
            }
            return Command::FAILURE;
        }

        $output->writeln('<info>Artifacts generated:</info>');
        $output->writeln('- ' . $datasetPath);
        $output->writeln('- ' . $modelPath);
        $output->writeln('- ' . $metadataPath);

        return Command::SUCCESS;
    }

    /**
     * @param list<array<string,int|float|string>> $rows
     * @param string[] $headers
     */
    private function writeCsv(string $path, array $rows, array $headers): void
    {
        $handle = fopen($path, 'wb');
        if ($handle === false) {
            throw new \RuntimeException('Unable to open file: ' . $path);
        }

        fputcsv($handle, $headers);
        foreach ($rows as $row) {
            $line = [];
            foreach ($headers as $header) {
                $line[] = $row[$header] ?? 0;
            }
            fputcsv($handle, $line);
        }

        fclose($handle);
    }
}
