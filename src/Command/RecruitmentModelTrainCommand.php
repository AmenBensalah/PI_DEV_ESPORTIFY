<?php

namespace App\Command;

use App\Entity\Candidature;
use App\Repository\CandidatureRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Process\Process;

#[AsCommand(
    name: 'app:recruitment-ai:train',
    description: 'Train recruitment compatibility model for team hiring (rank, region, availability).',
)]
class RecruitmentModelTrainCommand extends Command
{
    public function __construct(
        private CandidatureRepository $candidatureRepository,
        private KernelInterface $kernel
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $projectDir = $this->kernel->getProjectDir();
        $datasetPath = $projectDir . '/ml/recruitment_training_dataset.csv';
        $scriptPath = $projectDir . '/ml/recruitment_match_train.py';
        $modelPath = $projectDir . '/ml/recruitment_match_model.json';

        if (!is_file($scriptPath)) {
            $output->writeln('<error>Missing trainer script: ml/recruitment_match_train.py</error>');
            return Command::FAILURE;
        }

        /** @var Candidature[] $candidatures */
        $candidatures = $this->candidatureRepository->findAll();
        $rows = [];
        foreach ($candidatures as $c) {
            $team = $c->getEquipe();
            if ($team === null) {
                continue;
            }

            [$availabilityScore] = $this->availabilityScore($this->candidateText($c), (string) $c->getDisponibilite());
            $candidateRank = $this->mapRank((string) $c->getNiveau());
            $teamRank = $this->mapRank((string) $team->getClassement());
            $rankGap = 4.0;
            if ($candidateRank !== null && $teamRank !== null) {
                $rankGap = (float) abs($candidateRank - $teamRank);
            }

            $candidateRegion = mb_strtolower(trim((string) $c->getRegion()));
            $teamRegion = mb_strtolower(trim((string) $team->getRegion()));
            $regionMatch = 0;
            if ($teamRegion !== '' && $candidateRegion !== '' && ($candidateRegion === $teamRegion || str_contains($candidateRegion, $teamRegion) || str_contains($teamRegion, $candidateRegion))) {
                $regionMatch = 1;
            } elseif ($this->containsToken($this->candidateText($c), (string) $team->getRegion())) {
                $regionMatch = 1;
            }
            $status = mb_strtolower((string) $c->getStatut());
            $label = str_contains($status, 'accept') ? 1 : 0;

            if (!str_contains($status, 'accept') && !str_contains($status, 'refus')) {
                continue;
            }

            $rows[] = [
                'rank_gap' => $rankGap,
                'region_match' => $regionMatch,
                'availability_score' => $availabilityScore,
                'motivation_len' => mb_strlen((string) $c->getMotivation()),
                'reason_len' => mb_strlen((string) $c->getReason()),
                'play_style_len' => mb_strlen((string) $c->getPlayStyle()),
                'label' => $label,
            ];
        }

        if (count($rows) < 8) {
            $output->writeln('<error>Pas assez de candidatures labellisées (Accepté/Refusé) pour entraîner le modèle. Minimum: 8.</error>');
            return Command::FAILURE;
        }

        $this->writeCsv($datasetPath, $rows);
        $output->writeln(sprintf('<info>Dataset généré: %s (%d lignes)</info>', $datasetPath, count($rows)));

        $pythonBinaries = ['python', 'python3', 'py'];
        $trained = false;
        $lastError = '';

        foreach ($pythonBinaries as $bin) {
            $process = new Process([$bin, $scriptPath, $datasetPath, $modelPath]);
            $process->setTimeout(120);
            $process->run();
            if ($process->isSuccessful()) {
                $trained = true;
                $output->writeln(sprintf('<info>Modèle entraîné avec %s</info>', $bin));
                $stdout = trim($process->getOutput());
                if ($stdout !== '') {
                    $output->writeln($stdout);
                }
                break;
            }
            $lastError = trim($process->getErrorOutput() . "\n" . $process->getOutput());
        }

        if (!$trained) {
            $output->writeln('<comment>Python indisponible, fallback entraînement PHP en cours...</comment>');
            if ($lastError !== '') {
                $output->writeln($lastError);
            }
            $this->trainWithPhp($rows, $modelPath);
            $output->writeln('<info>Modèle entraîné avec fallback PHP.</info>');
        }

        $output->writeln('<info>Fichier modèle prêt: ml/recruitment_match_model.json</info>');
        return Command::SUCCESS;
    }

    /**
     * @param array<int, array<string, int|float>> $rows
     */
    private function writeCsv(string $path, array $rows): void
    {
        $headers = ['rank_gap', 'region_match', 'availability_score', 'motivation_len', 'reason_len', 'play_style_len', 'label'];
        $h = fopen($path, 'wb');
        if ($h === false) {
            throw new \RuntimeException('Unable to open dataset file: ' . $path);
        }

        fputcsv($h, $headers);
        foreach ($rows as $row) {
            $line = [];
            foreach ($headers as $header) {
                $line[] = $row[$header] ?? 0;
            }
            fputcsv($h, $line);
        }
        fclose($h);
    }

    private function mapRank(string $value): ?int
    {
        $v = mb_strtolower(trim($value));
        if ($v === '') {
            return null;
        }

        return match (true) {
            str_contains($v, 'debut') || str_contains($v, 'bronze') => 1,
            str_contains($v, 'amateur') || str_contains($v, 'argent') || str_contains($v, 'silver') => 2,
            str_contains($v, 'inter') || str_contains($v, 'or') || str_contains($v, 'gold') => 3,
            str_contains($v, 'confirm') || str_contains($v, 'plat') => 4,
            str_contains($v, 'pro') || str_contains($v, 'diam') => 5,
            str_contains($v, 'master') => 6,
            str_contains($v, 'challenger') => 7,
            default => null,
        };
    }

    private function candidateText(Candidature $candidature): string
    {
        return mb_strtolower(trim(
            (string) $candidature->getMotivation() . ' ' .
            (string) $candidature->getReason() . ' ' .
            (string) $candidature->getPlayStyle()
        ));
    }

    private function containsToken(string $haystack, string $needle): bool
    {
        $needle = mb_strtolower(trim($needle));
        if ($needle === '') {
            return false;
        }
        $tokens = preg_split('/[\s,;|\/-]+/u', $needle) ?: [];
        foreach ($tokens as $token) {
            $token = trim($token);
            if ($token === '' || mb_strlen($token) < 3) {
                continue;
            }
            if (str_contains($haystack, $token)) {
                return true;
            }
        }
        return str_contains($haystack, $needle);
    }

    /**
     * @return array{0:float,1:string}
     */
    private function availabilityScore(string $text, string $explicitValue = ''): array
    {
        $explicit = mb_strtolower(trim($explicitValue));
        if ($explicit !== '') {
            if (str_contains($explicit, 'élev') || str_contains($explicit, 'eleve') || str_contains($explicit, 'high')) {
                return [1.0, 'élevée'];
            }
            if (str_contains($explicit, 'moy')) {
                return [0.6, 'moyenne'];
            }
            if (str_contains($explicit, 'faib') || str_contains($explicit, 'low')) {
                return [0.2, 'faible'];
            }
        }

        $high = ['disponible tous les jours', 'full time', 'soir et weekend', '7/7', 'chaque jour', 'tous les soirs'];
        $medium = ['soir', 'weekend', 'week-end', 'après-midi', '3 fois par semaine', '2 fois par semaine'];
        $low = ['rarement', 'occasionnel', 'quand je peux', 'peu disponible', 'pas souvent'];

        foreach ($high as $k) {
            if (str_contains($text, $k)) {
                return [1.0, 'élevée'];
            }
        }
        foreach ($low as $k) {
            if (str_contains($text, $k)) {
                return [0.2, 'faible'];
            }
        }
        foreach ($medium as $k) {
            if (str_contains($text, $k)) {
                return [0.6, 'moyenne'];
            }
        }
        return [0.5, 'moyenne'];
    }

    /**
     * @param array<int, array<string, int|float>> $rows
     */
    private function trainWithPhp(array $rows, string $modelPath): void
    {
        $features = ['rank_gap', 'region_match', 'availability_score', 'motivation_len', 'reason_len', 'play_style_len'];
        $count = max(1, count($rows));

        $means = [];
        $stds = [];
        foreach ($features as $f) {
            $sum = 0.0;
            foreach ($rows as $r) {
                $sum += (float) ($r[$f] ?? 0);
            }
            $mean = $sum / $count;
            $means[$f] = $mean;

            $var = 0.0;
            foreach ($rows as $r) {
                $v = (float) ($r[$f] ?? 0);
                $var += ($v - $mean) * ($v - $mean);
            }
            $std = sqrt($var / $count);
            $stds[$f] = $std > 0.000001 ? $std : 1.0;
        }

        $weights = array_fill_keys($features, 0.0);
        $bias = 0.0;
        $lr = 0.03;
        $l2 = 0.001;
        $epochs = 1500;

        for ($epoch = 0; $epoch < $epochs; $epoch++) {
            $gradW = array_fill_keys($features, 0.0);
            $gradB = 0.0;

            foreach ($rows as $r) {
                $z = $bias;
                $label = (float) ((int) ($r['label'] ?? 0));
                $norm = [];
                foreach ($features as $f) {
                    $value = (float) ($r[$f] ?? 0);
                    $nv = ($value - (float) $means[$f]) / (float) $stds[$f];
                    $norm[$f] = $nv;
                    $z += ((float) $weights[$f]) * $nv;
                }

                $p = $this->sigmoid($z);
                $err = $p - $label;
                $gradB += $err;
                foreach ($features as $f) {
                    $gradW[$f] += $err * $norm[$f];
                }
            }

            foreach ($features as $f) {
                $grad = ((float) $gradW[$f] / $count) + ($l2 * (float) $weights[$f]);
                $weights[$f] = (float) $weights[$f] - ($lr * $grad);
            }
            $bias -= $lr * ($gradB / $count);
        }

        $payload = [
            'version' => 1,
            'features' => $features,
            'weights' => $weights,
            'bias' => $bias,
            'means' => $means,
            'stds' => $stds,
            'metrics' => [
                'samples' => count($rows),
                'trainer' => 'php_fallback',
            ],
        ];

        file_put_contents($modelPath, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    private function sigmoid(float $z): float
    {
        if ($z >= 0) {
            $e = exp(-$z);
            return 1.0 / (1.0 + $e);
        }
        $e = exp($z);
        return $e / (1.0 + $e);
    }
}
