<?php

namespace App\Service;

use App\Entity\Tournoi;
use App\Entity\User;
use App\Repository\TournoiMatchRepository;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Process\Process;

class SoloTournamentPredictionService
{
    public function __construct(
        private UserPerformanceAIService $userPerformanceAIService,
        private UserPerformanceMLService $userPerformanceMLService,
        private TournoiMatchRepository $tournoiMatchRepository,
        private KernelInterface $kernel
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function predictWinner(Tournoi $tournoi): array
    {
        if ($tournoi->getTypeTournoi() !== 'solo') {
            return [
                'available' => false,
                'reason' => 'Prediction disponible uniquement pour les tournois solo.',
            ];
        }

        if ($this->hasScoringStarted($tournoi)) {
            return [
                'available' => false,
                'reason' => 'La prediction initiale est disponible avant attribution des scores.',
            ];
        }

        $participants = array_values(array_filter(
            $tournoi->getParticipants()->toArray(),
            static fn (mixed $candidate): bool => $candidate instanceof User && $candidate->getId() !== null
        ));

        if (count($participants) < 2) {
            return [
                'available' => false,
                'reason' => 'Au moins 2 participants sont necessaires pour une prediction.',
            ];
        }

        $projectDir = $this->kernel->getProjectDir();
        $workDir = $projectDir . '/var/solo_tournament';
        if (!is_dir($workDir) && !mkdir($workDir, 0777, true) && !is_dir($workDir)) {
            return [
                'available' => false,
                'reason' => 'Impossible de preparer le dossier des predictions IA.',
            ];
        }

        $tournoiId = (int) ($tournoi->getIdTournoi() ?? 0);
        $this->cleanupLegacyTournamentCsvs($workDir);
        $inputCsv = $projectDir . '/ml/solo_input_latest.csv';
        $outputJson = $workDir . '/prediction_' . $tournoiId . '.json';
        $datasetCsv = $projectDir . '/ml/user_performance_dataset.csv';
        $scriptPath = $projectDir . '/ml/solo_tournament_predict.py';

        if (!is_file($scriptPath)) {
            return [
                'available' => false,
                'reason' => 'Script Python introuvable: ml/solo_tournament_predict.py',
            ];
        }

        $rows = $this->buildParticipantRows($participants, (string) $tournoi->getTypeGame(), $tournoiId);
        if (count($rows) < 2) {
            return [
                'available' => false,
                'reason' => 'Donnees joueurs insuffisantes pour la prediction.',
            ];
        }

        $headers = [
            'tournoi_id',
            'user_id',
            'name',
            'game_type',
            'overall_win_rate',
            'game_win_rate',
            'matches_played',
            'ml_win_probability',
        ];
        $this->writeCsv($inputCsv, $rows, $headers);

        $pythonCmd = $this->resolvePythonCommand($projectDir);
        if ($pythonCmd === null) {
            return [
                'available' => false,
                'reason' => 'Python introuvable. Definissez PYTHON_BIN ou installez Python 3.',
            ];
        }

        [$success, $error] = $this->runPythonScript($pythonCmd, $scriptPath, $inputCsv, $datasetCsv, $outputJson);
        if (!$success && $this->shouldInstallRequirements($error)) {
            $this->installRequirements($pythonCmd, $projectDir);
            [$success, $error] = $this->runPythonScript($pythonCmd, $scriptPath, $inputCsv, $datasetCsv, $outputJson);
        }

        if (!$success || !is_file($outputJson)) {
            return [
                'available' => false,
                'reason' => 'Echec prediction Python. Verifiez Python et les dependances ML (pandas, numpy, scikit-learn).',
                'debug' => $error !== '' ? $error : null,
            ];
        }

        $decoded = json_decode((string) file_get_contents($outputJson), true);
        if (!is_array($decoded)) {
            return [
                'available' => false,
                'reason' => 'Sortie IA invalide (JSON).',
            ];
        }

        return $decoded;
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
                $line[] = $row[$header] ?? '';
            }
            fputcsv($handle, $line);
        }

        fclose($handle);
    }

    private function cleanupLegacyTournamentCsvs(string $workDir): void
    {
        if (!is_dir($workDir)) {
            return;
        }

        foreach ((array) glob($workDir . '/input_*.csv') as $legacyPath) {
            if (is_file($legacyPath)) {
                @unlink($legacyPath);
            }
        }
    }

    /**
     * @param User[] $participants
     * @return array<int, array<string, int|float|string>>
     */
    private function buildParticipantRows(array $participants, string $gameType, int $tournoiId): array
    {
        $rows = [];
        $normalizedGameType = $this->normalizeGameType($gameType);

        foreach ($participants as $participant) {
            $userId = (int) ($participant->getId() ?? 0);
            if ($userId <= 0) {
                continue;
            }

            $report = $this->userPerformanceAIService->buildReport($participant);
            $summary = (array) ($report['summary'] ?? []);
            $perGame = (array) ($report['perGame'] ?? []);

            $overallWinRate = $this->clampPercent((float) ($summary['winRate'] ?? 0.0));
            $gameWinRate = $this->resolveGameWinRate($perGame, $normalizedGameType, $overallWinRate);
            $mlWinProbability = $this->resolveMlProbability($userId, $normalizedGameType, $gameWinRate);
            $matchesPlayed = max(0, (int) ($summary['matchesPlayed'] ?? 0));

            $rows[] = [
                'tournoi_id' => $tournoiId,
                'user_id' => $userId,
                'name' => $this->displayName($participant),
                'game_type' => $normalizedGameType,
                'overall_win_rate' => round($overallWinRate, 4),
                'game_win_rate' => round($gameWinRate, 4),
                'matches_played' => $matchesPlayed,
                'ml_win_probability' => round($mlWinProbability, 4),
            ];
        }

        return $rows;
    }

    private function hasScoringStarted(Tournoi $tournoi): bool
    {
        $matches = $this->tournoiMatchRepository->findByTournoiOrdered($tournoi);

        foreach ($matches as $match) {
            if ($match->getScoreA() !== null || $match->getScoreB() !== null || $match->getStatus() === 'played') {
                return true;
            }

            if ($match->getParticipantResults()->count() > 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<string, mixed> $perGame
     */
    private function resolveGameWinRate(array $perGame, string $normalizedGameType, float $fallback): float
    {
        foreach ($perGame as $gameName => $stats) {
            if (!is_array($stats)) {
                continue;
            }

            if ($this->normalizeGameType((string) $gameName) !== $normalizedGameType) {
                continue;
            }

            return $this->clampPercent((float) ($stats['winRate'] ?? $fallback));
        }

        return $fallback;
    }

    private function resolveMlProbability(int $userId, string $normalizedGameType, float $fallback): float
    {
        $prediction = $this->userPerformanceMLService->getPredictionForUser($userId);
        if (!is_array($prediction)) {
            return $fallback;
        }

        $byGameType = $prediction['byGameType'] ?? null;
        if (is_array($byGameType)) {
            foreach ($byGameType as $gameType => $payload) {
                if (!is_array($payload)) {
                    continue;
                }

                if ($this->normalizeGameType((string) $gameType) !== $normalizedGameType) {
                    continue;
                }

                return $this->clampPercent((float) ($payload['winProbability'] ?? $fallback));
            }
        }

        return $this->clampPercent((float) ($prediction['winProbability'] ?? $fallback));
    }

    /**
     * @return array{0: bool, 1: string}
     */
    private function runPythonScript(array $pythonCmd, string $scriptPath, string $inputCsv, string $datasetCsv, string $outputJson): array
    {
        $process = new Process(array_merge($pythonCmd, [$scriptPath, $inputCsv, $datasetCsv, $outputJson]));
        $process->setTimeout(180);
        $process->run();

        if ($process->isSuccessful()) {
            return [true, ''];
        }

        $error = trim($process->getErrorOutput() . "\n" . $process->getOutput());
        return [false, $error];
    }

    private function shouldInstallRequirements(string $error): bool
    {
        if ($error === '') {
            return false;
        }

        $lower = mb_strtolower($error);
        return str_contains($lower, 'modulenotfounderror')
            || str_contains($lower, 'no module named')
            || str_contains($lower, "can't import");
    }

    private function installRequirements(array $pythonCmd, string $projectDir): void
    {
        $requirements = $projectDir . '/ml/requirements.txt';
        if (!is_file($requirements)) {
            return;
        }

        $process = new Process(array_merge($pythonCmd, ['-m', 'pip', 'install', '-r', $requirements]));
        $process->setTimeout(300);
        $process->run();
    }

    /**
     * @return array<int, string>|null
     */
    private function resolvePythonCommand(string $projectDir): ?array
    {
        foreach ($this->pythonCommands($projectDir) as $candidate) {
            $probe = new Process(array_merge($candidate, ['--version']));
            $probe->setTimeout(10);
            $probe->run();

            if ($probe->isSuccessful()) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * @return array<int, array<int, string>>
     */
    private function pythonCommands(string $projectDir): array
    {
        $commands = [];

        $configured = trim((string) (
            $_ENV['PYTHON_BIN']
            ?? $_SERVER['PYTHON_BIN']
            ?? $this->readPythonBinFromDotEnvLocal($projectDir)
            ?? ''
        ));
        if ($configured !== '') {
            $commands[] = [$configured];
        }

        $venvWindows = $projectDir . '/ml/.venv/Scripts/python.exe';
        if (is_file($venvWindows)) {
            $commands[] = [$venvWindows];
        }

        $venvUnix = $projectDir . '/ml/.venv/bin/python';
        if (is_file($venvUnix)) {
            $commands[] = [$venvUnix];
        }

        $localAppData = (string) ($_SERVER['LOCALAPPDATA'] ?? $_ENV['LOCALAPPDATA'] ?? '');
        if ($localAppData !== '') {
            foreach ((array) glob($localAppData . '/Programs/Python/Python*/python.exe') as $pyPath) {
                if (is_file($pyPath)) {
                    $commands[] = [$pyPath];
                }
            }
        }

        $userProfile = (string) ($_SERVER['USERPROFILE'] ?? $_ENV['USERPROFILE'] ?? '');
        if ($userProfile !== '') {
            foreach ((array) glob($userProfile . '/AppData/Local/Programs/Python/Python*/python.exe') as $pyPath) {
                if (is_file($pyPath)) {
                    $commands[] = [$pyPath];
                }
            }
        }

        $commands[] = ['py', '-3'];
        $commands[] = ['py'];
        $commands[] = ['python'];
        $commands[] = ['python3'];

        return $commands;
    }

    private function readPythonBinFromDotEnvLocal(string $projectDir): ?string
    {
        $dotenvLocal = $projectDir . '/.env.local';
        if (!is_file($dotenvLocal)) {
            return null;
        }

        $lines = file($dotenvLocal, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (!is_array($lines)) {
            return null;
        }

        foreach ($lines as $line) {
            $trimmed = trim((string) $line);
            if ($trimmed === '' || str_starts_with($trimmed, '#')) {
                continue;
            }

            if (!str_starts_with($trimmed, 'PYTHON_BIN=')) {
                continue;
            }

            $value = trim((string) substr($trimmed, strlen('PYTHON_BIN=')));
            $value = trim($value, "\"'");

            return $value !== '' ? $value : null;
        }

        return null;
    }

    private function normalizeGameType(string $value): string
    {
        $normalized = mb_strtolower(trim($value));
        $normalized = str_replace([' ', '-'], '_', $normalized);

        return match ($normalized) {
            'battle_royale', 'battleroyale' => 'battle_royale',
            'sport' => 'sports',
            default => $normalized !== '' ? $normalized : 'other',
        };
    }

    private function clampPercent(float $value): float
    {
        if ($value < 0.0) {
            return 0.0;
        }

        if ($value > 100.0) {
            return 100.0;
        }

        return $value;
    }

    private function displayName(User $participant): string
    {
        $pseudo = trim((string) $participant->getPseudo());
        if ($pseudo !== '') {
            return $pseudo;
        }

        $name = trim((string) $participant->getNom());
        if ($name !== '') {
            return $name;
        }

        return (string) $participant->getEmail();
    }
}
