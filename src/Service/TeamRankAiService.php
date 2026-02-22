<?php

namespace App\Service;

use App\Entity\Equipe;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Process\Process;

class TeamRankAiService
{
    public function __construct(private KernelInterface $kernel)
    {
    }

    /**
     * @param array<string,mixed> $teamBalance
     * @param array<string,mixed> $teamPerformance
     * @param array<string,mixed> $teamLevelStats
     * @return array{rankLabel:string,rankScore:float,confidence:float,reasons:string[],source:string}
     */
    public function predict(Equipe $equipe, array $teamBalance, array $teamPerformance, array $teamLevelStats, int $membersCount): array
    {
        $payload = [
            'teamId' => $equipe->getId(),
            'balanceScore' => (float) ($teamBalance['balanceScore'] ?? 0),
            'acceptedLast30' => (int) ($teamPerformance['acceptedLast30'] ?? 0),
            'totalLast30' => (int) ($teamPerformance['totalLast30'] ?? 0),
            'trendTotal' => (int) ($teamPerformance['trendTotal'] ?? 0),
            'averageLevelScore' => (float) ($teamLevelStats['averageScore'] ?? 0),
            'isActive' => (bool) ($teamLevelStats['isActive'] ?? false),
            'membersCount' => $membersCount,
        ];

        $script = $this->kernel->getProjectDir() . '/ml/team_rank_predict.py';
        if (is_file($script)) {
            $result = $this->runPythonPredict($script, $payload);
            if ($result !== null) {
                return $result + ['source' => 'python_ml'];
            }
        }

        return $this->fallbackPredict($payload) + ['source' => 'php_fallback'];
    }

    /**
     * @param array<string,mixed> $payload
     * @return array{rankLabel:string,rankScore:float,confidence:float,reasons:string[]}|null
     */
    private function runPythonPredict(string $script, array $payload): ?array
    {
        $tmpDir = $this->kernel->getProjectDir() . '/var/team_ai';
        if (!is_dir($tmpDir) && !mkdir($tmpDir, 0777, true) && !is_dir($tmpDir)) {
            return null;
        }

        $input = $tmpDir . '/rank_input.json';
        $output = $tmpDir . '/rank_output.json';
        file_put_contents($input, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $bins = ['python', 'python3', 'py'];
        foreach ($bins as $bin) {
            $process = new Process([$bin, $script, $input, $output]);
            $process->setTimeout(20);
            $process->run();
            if (!$process->isSuccessful() || !is_file($output)) {
                continue;
            }

            $raw = file_get_contents($output);
            if (!is_string($raw) || $raw === '') {
                continue;
            }
            $decoded = json_decode($raw, true);
            if (!is_array($decoded)) {
                continue;
            }

            return [
                'rankLabel' => (string) ($decoded['rank_label'] ?? 'Bronze'),
                'rankScore' => (float) ($decoded['rank_score'] ?? 0.0),
                'confidence' => (float) ($decoded['confidence'] ?? 0.0),
                'reasons' => array_values(array_filter((array) ($decoded['reasons'] ?? []), static fn ($v): bool => is_string($v) && $v !== '')),
            ];
        }

        return null;
    }

    /**
     * @param array<string,mixed> $payload
     * @return array{rankLabel:string,rankScore:float,confidence:float,reasons:string[]}
     */
    private function fallbackPredict(array $payload): array
    {
        $balanceScore = (float) ($payload['balanceScore'] ?? 0.0);
        $acceptedLast30 = (float) ($payload['acceptedLast30'] ?? 0.0);
        $totalLast30 = (float) ($payload['totalLast30'] ?? 0.0);
        $trendTotal = (float) ($payload['trendTotal'] ?? 0.0);
        $avgLevel = (float) ($payload['averageLevelScore'] ?? 0.0);
        $isActive = (bool) ($payload['isActive'] ?? false);
        $membersCount = (float) ($payload['membersCount'] ?? 0.0);

        $conversion = $totalLast30 > 0 ? $acceptedLast30 / $totalLast30 : 0.0;
        $levelNorm = max(0.0, min(1.0, $avgLevel / 4.0));
        $balanceNorm = max(0.0, min(1.0, $balanceScore / 100.0));
        $trendNorm = max(0.0, min(1.0, ($trendTotal + 100.0) / 200.0));
        $memberNorm = max(0.0, min(1.0, $membersCount / 8.0));
        $activity = $isActive ? 1.0 : 0.3;

        $overall = (0.30 * $balanceNorm)
            + (0.25 * $levelNorm)
            + (0.20 * $conversion)
            + (0.10 * $trendNorm)
            + (0.10 * $activity)
            + (0.05 * $memberNorm);

        $rankScore = round(max(0.0, min(100.0, $overall * 100.0)), 1);
        $confidence = round(max(0.0, min(99.0, 45.0 + ($conversion * 25.0) + ($memberNorm * 15.0) + ($isActive ? 10.0 : 0.0))), 1);

        return [
            'rankLabel' => $this->rankFromScore($rankScore),
            'rankScore' => $rankScore,
            'confidence' => $confidence,
            'reasons' => [
                sprintf("Équilibre d'équipe: %.0f/100", $balanceScore),
                sprintf("Niveau moyen: %.2f/4", $avgLevel),
                sprintf("Taux d'acceptation récent: %.0f%%", $conversion * 100),
                $isActive ? "Équipe active" : "Activité récente faible",
            ],
        ];
    }

    private function rankFromScore(float $score): string
    {
        return match (true) {
            $score >= 90 => 'Challenger',
            $score >= 82 => 'Master',
            $score >= 74 => 'Diamant',
            $score >= 66 => 'Platine',
            $score >= 58 => 'Or',
            $score >= 50 => 'Argent',
            default => 'Bronze',
        };
    }
}

