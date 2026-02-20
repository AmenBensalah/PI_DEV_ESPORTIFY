<?php

namespace App\Command;

use App\Repository\CommandeRepository;
use App\Repository\PaymentRepository;
use App\Service\PaymentForecastMLService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Process\Process;

#[AsCommand(
    name: 'app:payment-forecast:train',
    description: 'Train ML model for revenue/orders/failure forecasting from payment history.',
)]
class PaymentForecastTrainCommand extends Command
{
    public function __construct(
        private PaymentRepository $paymentRepository,
        private CommandeRepository $commandeRepository,
        private PaymentForecastMLService $paymentForecastMLService,
        private KernelInterface $kernel
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $from = (new \DateTimeImmutable('today'))->modify('-240 days');
        $events = $this->paymentRepository->getEventsSince($from);
        if (count($events) < 1) {
            $statusCounts = $this->commandeRepository->countByStatut();
            $rows = $this->buildSyntheticRowsFromOrderStatus($statusCounts, 40);
            $output->writeln('<comment>Mode seed ML: aucun paiement, dataset synthetique depuis statuts commandes.</comment>');
        } else {
            $daily = $this->buildDailyStats($events);
            $rows = $this->buildTrainingRows($daily);
            if ($rows === []) {
                $rows = $this->buildSyntheticRowsFromEvents($events, 24);
                $output->writeln('<comment>Mode tiny-dataset: generation de lignes synthetiques.</comment>');
            }
        }
        if (count($rows) < 20) {
            $rows = $this->bootstrapRows($rows, 20);
            if (count($rows) < 20) {
                $output->writeln('<error>Dataset trop petit pour entrainer (minimum 20 lignes).</error>');
                return Command::FAILURE;
            }
            $output->writeln('<comment>Dataset bootstrap active pour completer les donnees.</comment>');
        }

        $datasetPath = $this->paymentForecastMLService->datasetPath();
        $workDir = dirname($datasetPath);
        if (!is_dir($workDir) && !mkdir($workDir, 0777, true) && !is_dir($workDir)) {
            $output->writeln('<error>Impossible de creer var/payment_forecast_ai.</error>');
            return Command::FAILURE;
        }

        $headers = [
            'rev_1d',
            'rev_3d',
            'rev_7d',
            'rev_14d',
            'rev_30d',
            'orders_1d',
            'orders_3d',
            'orders_7d',
            'orders_14d',
            'orders_30d',
            'fail_1d',
            'fail_7d',
            'fail_30d',
            'dow',
            'is_weekend',
            'next_rev',
            'next_orders',
            'next_fail_rate',
        ];
        $this->writeCsv($datasetPath, $rows, $headers);

        $scriptPath = $this->kernel->getProjectDir() . '/ml/payment_forecast_train.py';
        if (!is_file($scriptPath)) {
            $output->writeln('<error>Trainer Python manquant: ml/payment_forecast_train.py</error>');
            return Command::FAILURE;
        }

        $modelPath = $this->paymentForecastMLService->modelPath();
        $metadataPath = $this->paymentForecastMLService->metadataPath();

        $pythonBinaries = ['python', 'python3', 'py'];
        $lastError = '';
        $success = false;

        foreach ($pythonBinaries as $python) {
            $process = new Process([$python, $scriptPath, $datasetPath, $modelPath, $metadataPath]);
            $process->setTimeout(240);
            $process->run();

            if ($process->isSuccessful()) {
                $success = true;
                $output->writeln(sprintf('<info>Training forecast termine avec %s.</info>', $python));
                if (trim($process->getOutput()) !== '') {
                    $output->writeln(trim($process->getOutput()));
                }
                break;
            }

            $lastError = trim($process->getErrorOutput() . "\n" . $process->getOutput());
        }

        if (!$success) {
            $output->writeln('<error>Echec execution Python. Verifiez pandas/scikit-learn/joblib.</error>');
            if ($lastError !== '') {
                $output->writeln($lastError);
            }
            return Command::FAILURE;
        }

        $statusCounts = $this->commandeRepository->countByStatut();
        $paid = (int) ($statusCounts['paid'] ?? 0);
        $cancelled = (int) ($statusCounts['cancelled'] ?? 0);
        $pending = (int) ($statusCounts['pending_payment'] ?? 0);
        $attempts = max(1, $paid + $cancelled + $pending);
        $currentFailureRate = round(($cancelled / $attempts) * 100.0, 2);

        $output->writeln('<info>Artifacts:</info>');
        $output->writeln('- ' . $datasetPath);
        $output->writeln('- ' . $modelPath);
        $output->writeln('- ' . $metadataPath);
        $output->writeln(sprintf('<comment>Failure rate snapshot (orders status): %.2f%%</comment>', $currentFailureRate));

        return Command::SUCCESS;
    }

    /**
     * @param list<array{amount: float, status: string, createdAt: \DateTimeImmutable}> $events
     * @return array<string,array{revenue: float, paid: int, failed: int, attempts: int, fail_rate: float}>
     */
    private function buildDailyStats(array $events): array
    {
        $daily = [];
        foreach ($events as $event) {
            $day = $event['createdAt']->format('Y-m-d');
            if (!isset($daily[$day])) {
                $daily[$day] = [
                    'revenue' => 0.0,
                    'paid' => 0,
                    'failed' => 0,
                    'attempts' => 0,
                    'fail_rate' => 0.0,
                ];
            }

            $status = $event['status'];
            $isPaid = str_contains($status, 'paid') || str_contains($status, 'success') || str_contains($status, 'succeeded');
            $isFailed = str_contains($status, 'failed') || str_contains($status, 'cancel');
            $isAttempt = $isPaid || $isFailed || str_contains($status, 'pending');

            if ($isPaid) {
                $daily[$day]['revenue'] += (float) $event['amount'];
                $daily[$day]['paid']++;
            }
            if ($isFailed) {
                $daily[$day]['failed']++;
            }
            if ($isAttempt) {
                $daily[$day]['attempts']++;
            }
        }

        ksort($daily);
        foreach ($daily as $day => $stats) {
            $attempts = max(1, (int) $stats['attempts']);
            $daily[$day]['fail_rate'] = round(((int) $stats['failed'] / $attempts) * 100.0, 6);
        }

        return $daily;
    }

    /**
     * @param array<string,array{revenue: float, paid: int, failed: int, attempts: int, fail_rate: float}> $daily
     * @return list<array<string,int|float>>
     */
    private function buildTrainingRows(array $daily): array
    {
        $days = array_keys($daily);
        $count = count($days);
        if ($count < 10) {
            return [];
        }

        $rows = [];
        for ($i = 7; $i < $count - 1; $i++) {
            $currentDay = $days[$i];
            $nextDay = $days[$i + 1];
            $currentDate = new \DateTimeImmutable($currentDay . ' 00:00:00');

            $rows[] = [
                'rev_1d' => $this->sumWindow($days, $daily, $i, 1, 'revenue'),
                'rev_3d' => $this->sumWindow($days, $daily, $i, 3, 'revenue'),
                'rev_7d' => $this->sumWindow($days, $daily, $i, 7, 'revenue'),
                'rev_14d' => $this->sumWindow($days, $daily, $i, 14, 'revenue'),
                'rev_30d' => $this->sumWindow($days, $daily, $i, 30, 'revenue'),
                'orders_1d' => $this->sumWindow($days, $daily, $i, 1, 'paid'),
                'orders_3d' => $this->sumWindow($days, $daily, $i, 3, 'paid'),
                'orders_7d' => $this->sumWindow($days, $daily, $i, 7, 'paid'),
                'orders_14d' => $this->sumWindow($days, $daily, $i, 14, 'paid'),
                'orders_30d' => $this->sumWindow($days, $daily, $i, 30, 'paid'),
                'fail_1d' => $this->avgWindow($days, $daily, $i, 1, 'fail_rate'),
                'fail_7d' => $this->avgWindow($days, $daily, $i, 7, 'fail_rate'),
                'fail_30d' => $this->avgWindow($days, $daily, $i, 30, 'fail_rate'),
                'dow' => (int) $currentDate->format('N'),
                'is_weekend' => (int) ((int) $currentDate->format('N') >= 6),
                'next_rev' => (float) ($daily[$nextDay]['revenue'] ?? 0.0),
                'next_orders' => (int) ($daily[$nextDay]['paid'] ?? 0),
                'next_fail_rate' => (float) ($daily[$nextDay]['fail_rate'] ?? 0.0),
            ];
        }

        return $rows;
    }

    /**
     * @param array<string,int> $statusCounts
     * @return list<array<string,int|float>>
     */
    private function buildSyntheticRowsFromOrderStatus(array $statusCounts, int $count): array
    {
        $paid = (int) ($statusCounts['paid'] ?? 0);
        $cancelled = (int) ($statusCounts['cancelled'] ?? 0);
        $pending = (int) ($statusCounts['pending_payment'] ?? 0);
        $attempts = max(1, $paid + $cancelled + $pending);

        $baseOrders = max(1.0, $paid / 30.0);
        $baseFailure = ($cancelled / $attempts) * 100.0;
        $baseBasket = 18.0;

        $rows = [];
        for ($i = 0; $i < $count; $i++) {
            $dow = ($i % 7) + 1;
            $isWeekend = $dow >= 6 ? 1 : 0;
            $season = $isWeekend ? 1.08 : 0.97;
            $orders = max(0, (int) round($baseOrders * $season * (1.0 + mt_rand(-30, 30) / 100)));
            $revenue = round($orders * $baseBasket * (1.0 + mt_rand(-35, 35) / 100), 6);
            $fail = min(100.0, max(0.0, round($baseFailure + (mt_rand(-450, 450) / 100.0), 6)));

            $rows[] = [
                'rev_1d' => round($revenue * (0.85 + mt_rand(-8, 8) / 100), 6),
                'rev_3d' => round($revenue * (2.6 + mt_rand(-20, 20) / 100), 6),
                'rev_7d' => round($revenue * (6.2 + mt_rand(-45, 45) / 100), 6),
                'rev_14d' => round($revenue * (12.9 + mt_rand(-90, 90) / 100), 6),
                'rev_30d' => round($revenue * (27.0 + mt_rand(-180, 180) / 100), 6),
                'orders_1d' => max(0, (int) round($orders * (0.9 + mt_rand(-10, 10) / 100))),
                'orders_3d' => max(0, (int) round($orders * (2.7 + mt_rand(-20, 20) / 100))),
                'orders_7d' => max(0, (int) round($orders * (6.4 + mt_rand(-45, 45) / 100))),
                'orders_14d' => max(0, (int) round($orders * (13.0 + mt_rand(-90, 90) / 100))),
                'orders_30d' => max(0, (int) round($orders * (27.8 + mt_rand(-180, 180) / 100))),
                'fail_1d' => $fail,
                'fail_7d' => min(100.0, max(0.0, round($fail + (mt_rand(-300, 300) / 100.0), 6))),
                'fail_30d' => min(100.0, max(0.0, round($fail + (mt_rand(-400, 400) / 100.0), 6))),
                'dow' => $dow,
                'is_weekend' => $isWeekend,
                'next_rev' => $revenue,
                'next_orders' => $orders,
                'next_fail_rate' => $fail,
            ];
        }

        return $rows;
    }

    /**
     * @param list<array{amount: float, status: string, createdAt: \DateTimeImmutable}> $events
     * @return list<array<string,int|float>>
     */
    private function buildSyntheticRowsFromEvents(array $events, int $count): array
    {
        $paidAmounts = [];
        $paidCount = 0;
        $failedCount = 0;
        $attemptCount = 0;
        foreach ($events as $event) {
            $status = (string) ($event['status'] ?? '');
            $isPaid = str_contains($status, 'paid') || str_contains($status, 'success') || str_contains($status, 'succeeded');
            $isFailed = str_contains($status, 'failed') || str_contains($status, 'cancel');
            $isAttempt = $isPaid || $isFailed || str_contains($status, 'pending');

            if ($isPaid) {
                $paidAmounts[] = (float) $event['amount'];
                $paidCount++;
            }
            if ($isFailed) {
                $failedCount++;
            }
            if ($isAttempt) {
                $attemptCount++;
            }
        }

        $avgAmount = $paidAmounts === [] ? 0.0 : ((float) array_sum($paidAmounts) / max(1, count($paidAmounts)));
        $avgOrdersPerDay = max(1.0, $paidCount / 7.0);
        $baseFail = $attemptCount > 0 ? ($failedCount / $attemptCount) * 100.0 : 0.0;

        $rows = [];
        for ($i = 0; $i < $count; $i++) {
            $dow = ($i % 7) + 1;
            $isWeekend = $dow >= 6 ? 1 : 0;
            $season = $isWeekend ? 1.12 : 0.98;
            $noiseRev = 1.0 + (mt_rand(-22, 22) / 100.0);
            $noiseOrd = 1.0 + (mt_rand(-25, 25) / 100.0);

            $nextOrders = max(0, (int) round($avgOrdersPerDay * $season * $noiseOrd));
            $nextRev = round(($avgAmount * max(1, $nextOrders)) * $noiseRev, 6);
            $nextFail = min(100.0, max(0.0, round($baseFail + (mt_rand(-400, 400) / 100.0), 6)));

            $rows[] = [
                'rev_1d' => round($nextRev * (0.9 + mt_rand(-8, 8) / 100), 6),
                'rev_3d' => round($nextRev * (2.7 + mt_rand(-20, 20) / 100), 6),
                'rev_7d' => round($nextRev * (6.5 + mt_rand(-45, 45) / 100), 6),
                'rev_14d' => round($nextRev * (13.2 + mt_rand(-90, 90) / 100), 6),
                'rev_30d' => round($nextRev * (27.5 + mt_rand(-180, 180) / 100), 6),
                'orders_1d' => max(0, (int) round($nextOrders * (0.9 + mt_rand(-12, 12) / 100))),
                'orders_3d' => max(0, (int) round($nextOrders * (2.8 + mt_rand(-20, 20) / 100))),
                'orders_7d' => max(0, (int) round($nextOrders * (6.6 + mt_rand(-45, 45) / 100))),
                'orders_14d' => max(0, (int) round($nextOrders * (13.5 + mt_rand(-90, 90) / 100))),
                'orders_30d' => max(0, (int) round($nextOrders * (28.0 + mt_rand(-180, 180) / 100))),
                'fail_1d' => $nextFail,
                'fail_7d' => min(100.0, max(0.0, round($nextFail + (mt_rand(-250, 250) / 100.0), 6))),
                'fail_30d' => min(100.0, max(0.0, round($nextFail + (mt_rand(-350, 350) / 100.0), 6))),
                'dow' => $dow,
                'is_weekend' => $isWeekend,
                'next_rev' => $nextRev,
                'next_orders' => $nextOrders,
                'next_fail_rate' => $nextFail,
            ];
        }

        return $rows;
    }

    /**
     * @param list<array<string,int|float>> $rows
     * @return list<array<string,int|float>>
     */
    private function bootstrapRows(array $rows, int $targetCount): array
    {
        if ($rows === []) {
            return [];
        }

        $bootstrapped = $rows;
        while (count($bootstrapped) < $targetCount) {
            $source = $rows[array_rand($rows)];
            $candidate = $source;

            foreach (['rev_1d', 'rev_3d', 'rev_7d', 'rev_14d', 'rev_30d', 'next_rev'] as $field) {
                $base = (float) ($candidate[$field] ?? 0.0);
                $factor = 1.0 + (mt_rand(-15, 15) / 100.0);
                $candidate[$field] = round(max(0.0, $base * $factor), 6);
            }

            foreach (['orders_1d', 'orders_3d', 'orders_7d', 'orders_14d', 'orders_30d', 'next_orders'] as $field) {
                $base = (float) ($candidate[$field] ?? 0.0);
                $factor = 1.0 + (mt_rand(-20, 20) / 100.0);
                $candidate[$field] = max(0, (int) round($base * $factor));
            }

            foreach (['fail_1d', 'fail_7d', 'fail_30d', 'next_fail_rate'] as $field) {
                $base = (float) ($candidate[$field] ?? 0.0);
                $delta = mt_rand(-300, 300) / 100.0;
                $candidate[$field] = min(100.0, max(0.0, round($base + $delta, 6)));
            }

            $bootstrapped[] = $candidate;
        }

        return $bootstrapped;
    }

    /**
     * @param array<int,string> $days
     * @param array<string,array{revenue: float, paid: int, failed: int, attempts: int, fail_rate: float}> $daily
     */
    private function sumWindow(array $days, array $daily, int $endIndex, int $window, string $field): float
    {
        $start = max(0, $endIndex - $window + 1);
        $sum = 0.0;
        for ($j = $start; $j <= $endIndex; $j++) {
            $sum += (float) ($daily[$days[$j]][$field] ?? 0.0);
        }
        return round($sum, 6);
    }

    /**
     * @param array<int,string> $days
     * @param array<string,array{revenue: float, paid: int, failed: int, attempts: int, fail_rate: float}> $daily
     */
    private function avgWindow(array $days, array $daily, int $endIndex, int $window, string $field): float
    {
        $start = max(0, $endIndex - $window + 1);
        $sum = 0.0;
        $count = 0;
        for ($j = $start; $j <= $endIndex; $j++) {
            $sum += (float) ($daily[$days[$j]][$field] ?? 0.0);
            $count++;
        }
        return $count > 0 ? round($sum / $count, 6) : 0.0;
    }

    /**
     * @param list<array<string,int|float>> $rows
     * @param list<string> $headers
     */
    private function writeCsv(string $path, array $rows, array $headers): void
    {
        $handle = fopen($path, 'wb');
        if ($handle === false) {
            throw new \RuntimeException('Cannot open csv: ' . $path);
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
