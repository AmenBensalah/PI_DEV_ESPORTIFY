<?php

namespace App\Command;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\UserPerformanceAIService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Process\Process;

#[AsCommand(
    name: 'app:user-ai:train',
    description: 'Train isolated user performance AI model and generate per-user predictions.',
)]
class UserPerformanceTrainCommand extends Command
{
    private const PREDICT_GAME_TYPES = ['fps', 'sports', 'battle_royale', 'mind', 'other'];

    public function __construct(
        private UserRepository $userRepository,
        private UserPerformanceAIService $userPerformanceAIService,
        private KernelInterface $kernel
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $projectDir = $this->kernel->getProjectDir();
        $workDir = $projectDir . '/var/user_ai';
        if (!is_dir($workDir) && !mkdir($workDir, 0777, true) && !is_dir($workDir)) {
            $output->writeln('<error>Unable to create var/user_ai directory.</error>');
            return Command::FAILURE;
        }

        $trainCsv = $workDir . '/train.csv';
        $predictCsv = $workDir . '/predict.csv';
        $predictionsJson = $workDir . '/predictions.json';
        $modelInfoJson = $workDir . '/model_info.json';
        $datasetCsv = $projectDir . '/ml/user_performance_dataset.csv';
        $predictSnapshotCsv = $projectDir . '/ml/user_performance_predict_snapshot.csv';

        $trainRows = [];
        $predictRows = [];

        /** @var User[] $users */
        $users = $this->userRepository->findAll();
        foreach ($users as $user) {
            $userId = $user->getId();
            if ($userId === null) {
                continue;
            }

            $events = $this->userPerformanceAIService->getUserMatchEvents($user, true);
            if ($events === []) {
                continue;
            }

            [$userTrainRows, $userPredictRows] = $this->buildRowsForUser($events, $userId);
            if ($userTrainRows !== []) {
                $trainRows = array_merge($trainRows, $userTrainRows);
            }
            if ($userPredictRows !== []) {
                $predictRows = array_merge($predictRows, $userPredictRows);
            }
        }

        $output->writeln(sprintf('Collected %d training rows and %d prediction rows.', count($trainRows), count($predictRows)));

        if ($predictRows === []) {
            file_put_contents($predictionsJson, json_encode(new \stdClass(), JSON_PRETTY_PRINT));
            file_put_contents($modelInfoJson, json_encode([
                'status' => 'no_data',
                'generatedAt' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
                'samples' => 0,
            ], JSON_PRETTY_PRINT));

            $output->writeln('<comment>No user match history found. Wrote empty prediction files.</comment>');
            return Command::SUCCESS;
        }

        $trainHeaders = [
            'user_id',
            'prev_matches',
            'prev_win_rate',
            'prev_draw_rate',
            'prev_loss_rate',
            'prev_avg_points',
            'prev_form5_score',
            'prev_form10_score',
            'prev_recent_win_streak',
            'prev_recent_loss_streak',
            'prev_game_matches',
            'prev_game_win_rate',
            'prev_game_draw_rate',
            'prev_game_loss_rate',
            'prev_game_avg_points',
            'prev_game_form5_score',
            'is_squad',
            'game_fps',
            'game_sports',
            'game_battle_royale',
            'game_mind',
            'game_other',
            'label_win',
        ];

        $predictHeaders = [
            'user_id',
            'game_key',
            'prev_matches',
            'prev_win_rate',
            'prev_draw_rate',
            'prev_loss_rate',
            'prev_avg_points',
            'prev_form5_score',
            'prev_form10_score',
            'prev_recent_win_streak',
            'prev_recent_loss_streak',
            'prev_game_matches',
            'prev_game_win_rate',
            'prev_game_draw_rate',
            'prev_game_loss_rate',
            'prev_game_avg_points',
            'prev_game_form5_score',
            'is_squad',
            'game_fps',
            'game_sports',
            'game_battle_royale',
            'game_mind',
            'game_other',
            'sample_size',
        ];

        $this->writeCsv($trainCsv, $trainRows, $trainHeaders);
        $this->writeCsv($predictCsv, $predictRows, $predictHeaders);
        // Keep a visible project-level dataset snapshot (outside /var) for debugging/training inspection.
        $this->writeCsv($datasetCsv, $trainRows, $trainHeaders);
        $this->writeCsv($predictSnapshotCsv, $predictRows, $predictHeaders);

        $scriptPath = $projectDir . '/ml/user_performance_train.py';
        if (!is_file($scriptPath)) {
            $output->writeln('<error>Python script not found: ml/user_performance_train.py</error>');
            return Command::FAILURE;
        }

        $pythonBinaries = ['python', 'python3', 'py'];
        $lastErrorOutput = '';
        $success = false;

        foreach ($pythonBinaries as $python) {
            $process = new Process([$python, $scriptPath, $trainCsv, $predictCsv, $predictionsJson, $modelInfoJson]);
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
            $output->writeln('<error>Unable to run Python trainer. Install Python + pandas + scikit-learn.</error>');
            if ($lastErrorOutput !== '') {
                $output->writeln($lastErrorOutput);
            }
            return Command::FAILURE;
        }

        $output->writeln('<info>Files generated:</info>');
        $output->writeln('- ' . $trainCsv);
        $output->writeln('- ' . $predictCsv);
        $output->writeln('- ' . $predictionsJson);
        $output->writeln('- ' . $modelInfoJson);
        $output->writeln('- ' . $datasetCsv);
        $output->writeln('- ' . $predictSnapshotCsv);

        return Command::SUCCESS;
    }

    /**
     * @param array<int, array<string, mixed>> $events
     * @return array{0: array<int, array<string, int|float>>, 1: array<int, array<string, int|float|string>>}
     */
    private function buildRowsForUser(array $events, int $userId): array
    {
        $trainRows = [];
        $predictRows = [];

        $history = [
            'results' => [],
            'points' => [],
            'wins' => 0,
            'draws' => 0,
            'losses' => 0,
            'totalPoints' => 0,
            'byGame' => [],
        ];

        foreach ($events as $event) {
            $result = $event['result'] ?? null;
            if ($result !== 'W' && $result !== 'D' && $result !== 'L') {
                continue;
            }

            $gameKey = $this->normalizeGameKey((string) ($event['typeGame'] ?? ''));
            $isSquad = strtolower((string) ($event['typeTournoi'] ?? '')) === 'squad';
            $feature = $this->buildFeatureVectorForGame($history, $gameKey, $isSquad);
            $feature['user_id'] = $userId;
            $feature['label_win'] = $result === 'W' ? 1 : 0;
            $trainRows[] = $feature;

            $this->appendHistoryEvent($history, $gameKey, $result, (int) ($event['points'] ?? 0));
        }

        if (($history['results'] ?? []) === []) {
            return [$trainRows, []];
        }

        $lastEvent = end($events);
        if (!is_array($lastEvent)) {
            return [$trainRows, []];
        }

        $isSquad = strtolower((string) ($lastEvent['typeTournoi'] ?? '')) === 'squad';
        $predictRows = $this->buildPredictRowsForAllGameTypes($history, $userId, $isSquad);

        return [$trainRows, $predictRows];
    }

    /**
     * @param array<string, mixed> $history
     * @return array<int, array<string, int|float|string>>
     */
    private function buildPredictRowsForAllGameTypes(array $history, int $userId, bool $isSquad): array
    {
        $rows = [];
        $sampleSize = count((array) ($history['results'] ?? []));

        foreach (self::PREDICT_GAME_TYPES as $gameKey) {
            $row = $this->buildFeatureVectorForGame($history, $gameKey, $isSquad);
            $row['user_id'] = $userId;
            $row['game_key'] = $gameKey;
            $row['sample_size'] = $sampleSize;
            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * @param array<string, mixed> $history
     * @return array<string, int|float>
     */
    private function buildFeatureVectorForGame(array $history, string $gameKey, bool $isSquad): array
    {
        $historyResults = (array) ($history['results'] ?? []);
        $historyPoints = (array) ($history['points'] ?? []);
        $prevMatches = count($historyResults);

        $wins = (int) ($history['wins'] ?? 0);
        $draws = (int) ($history['draws'] ?? 0);
        $losses = (int) ($history['losses'] ?? 0);

        $gameHistory = (array) (($history['byGame'][$gameKey] ?? null) ?: []);
        $gameResults = (array) ($gameHistory['results'] ?? []);
        $gamePoints = (array) ($gameHistory['points'] ?? []);
        $prevGameMatches = count($gameResults);
        $gameWins = (int) ($gameHistory['wins'] ?? 0);
        $gameDraws = (int) ($gameHistory['draws'] ?? 0);
        $gameLosses = (int) ($gameHistory['losses'] ?? 0);

        return [
            'prev_matches' => $prevMatches,
            'prev_win_rate' => round($this->safeRate($wins, $prevMatches), 6),
            'prev_draw_rate' => round($this->safeRate($draws, $prevMatches), 6),
            'prev_loss_rate' => round($this->safeRate($losses, $prevMatches), 6),
            'prev_avg_points' => round($this->average($historyPoints), 6),
            'prev_form5_score' => round($this->computeFormScore($historyResults, 5), 6),
            'prev_form10_score' => round($this->computeFormScore($historyResults, 10), 6),
            'prev_recent_win_streak' => $this->computeStreak($historyResults, 'W'),
            'prev_recent_loss_streak' => $this->computeStreak($historyResults, 'L'),
            'prev_game_matches' => $prevGameMatches,
            'prev_game_win_rate' => round($this->safeRate($gameWins, $prevGameMatches), 6),
            'prev_game_draw_rate' => round($this->safeRate($gameDraws, $prevGameMatches), 6),
            'prev_game_loss_rate' => round($this->safeRate($gameLosses, $prevGameMatches), 6),
            'prev_game_avg_points' => round($this->average($gamePoints), 6),
            'prev_game_form5_score' => round($this->computeFormScore($gameResults, 5), 6),
            'is_squad' => $isSquad ? 1 : 0,
        ] + $this->oneHotGameColumns($gameKey);
    }

    /**
     * @param array<string, mixed> $history
     */
    private function appendHistoryEvent(array &$history, string $gameKey, string $result, int $points): void
    {
        $history['results'][] = $result;
        $history['points'][] = $points;
        $history['totalPoints'] = (int) ($history['totalPoints'] ?? 0) + $points;

        if ($result === 'W') {
            $history['wins'] = (int) ($history['wins'] ?? 0) + 1;
        } elseif ($result === 'D') {
            $history['draws'] = (int) ($history['draws'] ?? 0) + 1;
        } else {
            $history['losses'] = (int) ($history['losses'] ?? 0) + 1;
        }

        if (!isset($history['byGame'][$gameKey]) || !is_array($history['byGame'][$gameKey])) {
            $history['byGame'][$gameKey] = [
                'results' => [],
                'points' => [],
                'wins' => 0,
                'draws' => 0,
                'losses' => 0,
                'totalPoints' => 0,
            ];
        }

        $history['byGame'][$gameKey]['results'][] = $result;
        $history['byGame'][$gameKey]['points'][] = $points;
        $history['byGame'][$gameKey]['totalPoints'] = (int) ($history['byGame'][$gameKey]['totalPoints'] ?? 0) + $points;

        if ($result === 'W') {
            $history['byGame'][$gameKey]['wins'] = (int) ($history['byGame'][$gameKey]['wins'] ?? 0) + 1;
        } elseif ($result === 'D') {
            $history['byGame'][$gameKey]['draws'] = (int) ($history['byGame'][$gameKey]['draws'] ?? 0) + 1;
        } else {
            $history['byGame'][$gameKey]['losses'] = (int) ($history['byGame'][$gameKey]['losses'] ?? 0) + 1;
        }
    }

    /**
     * @param string[] $historyResults
     */
    private function computeFormScore(array $historyResults, int $window): float
    {
        if ($historyResults === [] || $window <= 0) {
            return 0.0;
        }

        $recent = array_slice($historyResults, -$window);
        $score = 0.0;
        foreach ($recent as $result) {
            if ($result === 'W') {
                $score += 1.0;
            } elseif ($result === 'L') {
                $score -= 1.0;
            }
        }

        $denominator = max(1, count($recent));
        return $score / $denominator;
    }

    /**
     * @param string[] $historyResults
     */
    private function computeStreak(array $historyResults, string $target): int
    {
        if ($historyResults === []) {
            return 0;
        }

        $streak = 0;
        for ($i = count($historyResults) - 1; $i >= 0; $i--) {
            if ($historyResults[$i] !== $target) {
                break;
            }
            $streak++;
        }

        return $streak;
    }

    /**
     * @param array<int, int|float> $values
     */
    private function average(array $values): float
    {
        if ($values === []) {
            return 0.0;
        }

        return array_sum($values) / count($values);
    }

    private function safeRate(int $count, int $total): float
    {
        if ($total <= 0) {
            return 0.0;
        }

        return $count / $total;
    }

    /**
     * @return array<string, int>
     */
    private function oneHotGameColumns(string $gameKey): array
    {
        return [
            'game_fps' => $gameKey === 'fps' ? 1 : 0,
            'game_sports' => $gameKey === 'sports' ? 1 : 0,
            'game_battle_royale' => $gameKey === 'battle_royale' ? 1 : 0,
            'game_mind' => $gameKey === 'mind' ? 1 : 0,
            'game_other' => $gameKey === 'other' ? 1 : 0,
        ];
    }

    private function normalizeGameKey(string $typeGame): string
    {
        $normalized = strtolower(trim($typeGame));
        $normalized = str_replace(['-', ' '], '_', $normalized);

        return match ($normalized) {
            'fps' => 'fps',
            'sports', 'sport' => 'sports',
            'battle_royale', 'battleroyale' => 'battle_royale',
            'mind' => 'mind',
            default => 'other',
        };
    }

    /**
     * @param array<int, array<string, int|float|string>> $rows
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
