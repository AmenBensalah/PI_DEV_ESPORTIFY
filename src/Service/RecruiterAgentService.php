<?php

namespace App\Service;

use App\Entity\Candidature;
use App\Entity\Equipe;

class RecruiterAgentService
{
    private const MODEL_PATH = '/ml/recruitment_match_model.json';

    /**
     * @param Candidature[] $candidatures
     * @param array{rank?:string,region?:string,availability?:string} $filters
     * @return array<int, array{
     *     candidature:Candidature,
     *     score:int,
     *     mlProbability:float,
     *     availability:string,
     *     reasons:string[]
     * }>
     */
    public function topCandidates(Equipe $equipe, array $candidatures, array $filters = [], int $limit = 5): array
    {
        $rows = [];
        foreach ($candidatures as $candidature) {
            if (!$candidature instanceof Candidature) {
                continue;
            }
            if (mb_strtolower((string) $candidature->getStatut()) !== mb_strtolower('En attente')) {
                continue;
            }

            if (!$this->passesFilters($candidature, $equipe, $filters)) {
                continue;
            }

            [$features, $reasons, $availabilityLabel] = $this->buildFeatures($candidature, $equipe, $filters);
            $prob = $this->predictProbability($features);
            $score = (int) round($prob * 100);
            $reasonAiScore = $candidature->getReasonAiScore();
            if ($reasonAiScore !== null) {
                $score = min(100, $score + (int) round($reasonAiScore * 0.12));
            }

            $rows[] = [
                'candidature' => $candidature,
                'score' => $score,
                'mlProbability' => $prob,
                'availability' => $availabilityLabel,
                'reasons' => $reasons,
            ];
        }

        usort($rows, static fn (array $a, array $b): int => $b['score'] <=> $a['score']);

        return array_slice($rows, 0, max(1, $limit));
    }

    /**
     * @param array<string,float> $features
     */
    private function predictProbability(array $features): float
    {
        $model = $this->loadModel();
        if ($model === null) {
            return $this->fallbackProbability($features);
        }

        $weights = $model['weights'] ?? [];
        $means = $model['means'] ?? [];
        $stds = $model['stds'] ?? [];
        $bias = (float) ($model['bias'] ?? 0.0);

        $z = $bias;
        foreach ($weights as $name => $weight) {
            $value = (float) ($features[$name] ?? 0.0);
            $mean = (float) ($means[$name] ?? 0.0);
            $std = (float) ($stds[$name] ?? 1.0);
            if ($std <= 0.000001) {
                $std = 1.0;
            }
            $z += ((float) $weight) * (($value - $mean) / $std);
        }

        return $this->sigmoid($z);
    }

    /**
     * @param array<string,float> $features
     */
    private function fallbackProbability(array $features): float
    {
        $rankScore = max(0.0, 1.0 - ((float) ($features['rank_gap'] ?? 4.0) / 4.0));
        $regionScore = (float) ($features['region_match'] ?? 0.0);
        $availability = (float) ($features['availability_score'] ?? 0.0);
        $textScore = min(1.0, (((float) ($features['motivation_len'] ?? 0.0)) + ((float) ($features['reason_len'] ?? 0.0))) / 500.0);

        $blend = (0.45 * $rankScore) + (0.20 * $regionScore) + (0.25 * $availability) + (0.10 * $textScore);
        return max(0.01, min(0.99, $blend));
    }

    /**
     * @param array{rank?:string,region?:string,availability?:string} $filters
     * @return array{0:array<string,float>,1:string[],2:string}
     */
    private function buildFeatures(Candidature $candidature, Equipe $equipe, array $filters): array
    {
        $candidateRank = $this->mapRank((string) $candidature->getNiveau());
        $teamRank = $this->mapRank((string) $equipe->getClassement());
        $rankGap = 4.0;
        if ($candidateRank !== null && $teamRank !== null) {
            $rankGap = (float) abs($candidateRank - $teamRank);
        }

        $textBlob = $this->candidateText($candidature);
        $targetRegion = trim((string) ($filters['region'] ?? ''));
        if ($targetRegion === '') {
            $targetRegion = (string) $equipe->getRegion();
        }
        $regionMatch = $this->regionMatches($candidature, $targetRegion) ? 1.0 : 0.0;

        [$availabilityScore, $availabilityLabel] = $this->availabilityScore($textBlob, (string) $candidature->getDisponibilite());

        $features = [
            'rank_gap' => $rankGap,
            'region_match' => $regionMatch,
            'availability_score' => $availabilityScore,
            'motivation_len' => (float) mb_strlen((string) $candidature->getMotivation()),
            'reason_len' => (float) mb_strlen((string) $candidature->getReason()),
            'play_style_len' => (float) mb_strlen((string) $candidature->getPlayStyle()),
        ];

        $reasons = [];
        if ($rankGap <= 1.0) {
            $reasons[] = 'Rank très compatible';
        } elseif ($rankGap <= 2.0) {
            $reasons[] = 'Rank compatible';
        } else {
            $reasons[] = 'Rank éloigné';
        }
        $reasons[] = $regionMatch > 0.5 ? 'Région compatible' : 'Région non confirmée';
        $reasons[] = 'Disponibilité: ' . $availabilityLabel;

        return [$features, $reasons, $availabilityLabel];
    }

    /**
     * @param array{rank?:string,region?:string,availability?:string} $filters
     */
    private function passesFilters(Candidature $candidature, Equipe $equipe, array $filters): bool
    {
        $rankFilter = trim((string) ($filters['rank'] ?? ''));
        if ($rankFilter !== '') {
            $requiredRank = $this->mapRank($rankFilter);
            $candidateRank = $this->mapRank((string) $candidature->getNiveau());
            if ($requiredRank !== null && $candidateRank !== null && abs($requiredRank - $candidateRank) > 0) {
                return false;
            }
        }

        $textBlob = $this->candidateText($candidature);
        $regionFilter = trim((string) ($filters['region'] ?? ''));
        if ($regionFilter !== '' && !$this->regionMatches($candidature, $regionFilter)) {
            return false;
        }

        $availabilityFilter = mb_strtolower(trim((string) ($filters['availability'] ?? '')));
        if ($availabilityFilter !== '') {
            [, $label] = $this->availabilityScore($textBlob, (string) $candidature->getDisponibilite());
            if ($availabilityFilter === 'high' && $label !== 'élevée') {
                return false;
            }
            if ($availabilityFilter === 'medium' && $label !== 'moyenne') {
                return false;
            }
            if ($availabilityFilter === 'low' && $label !== 'faible') {
                return false;
            }
        }

        // If region filter absent, keep region soft against team profile only (no hard rejection).
        $teamRegion = trim((string) $equipe->getRegion());
        if ($regionFilter === '' && $teamRegion !== '' && !$this->containsToken($textBlob, $teamRegion)) {
            // Not a blocker.
        }

        return true;
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
            (string) $candidature->getPlayStyle() . ' ' .
            (string) $candidature->getRegion() . ' ' .
            (string) $candidature->getDisponibilite()
        ));
    }

    private function regionMatches(Candidature $candidature, string $targetRegion): bool
    {
        $targetRegion = mb_strtolower(trim($targetRegion));
        if ($targetRegion === '') {
            return false;
        }

        $candidateRegion = mb_strtolower(trim((string) $candidature->getRegion()));
        if ($candidateRegion !== '' && ($candidateRegion === $targetRegion || str_contains($candidateRegion, $targetRegion) || str_contains($targetRegion, $candidateRegion))) {
            return true;
        }

        return $this->containsToken($this->candidateText($candidature), $targetRegion);
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
     * @return array{weights:array<string,float>,means:array<string,float>,stds:array<string,float>,bias:float}|null
     */
    private function loadModel(): ?array
    {
        $path = dirname(__DIR__, 2) . self::MODEL_PATH;
        if (!is_file($path)) {
            return null;
        }

        $raw = file_get_contents($path);
        if (!is_string($raw) || $raw === '') {
            return null;
        }
        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return null;
        }

        if (!isset($decoded['weights'], $decoded['means'], $decoded['stds'])) {
            return null;
        }

        return [
            'weights' => is_array($decoded['weights']) ? $decoded['weights'] : [],
            'means' => is_array($decoded['means']) ? $decoded['means'] : [],
            'stds' => is_array($decoded['stds']) ? $decoded['stds'] : [],
            'bias' => (float) ($decoded['bias'] ?? 0.0),
        ];
    }

    private function sigmoid(float $z): float
    {
        if ($z >= 0) {
            $exp = exp(-$z);
            return 1.0 / (1.0 + $exp);
        }

        $exp = exp($z);
        return $exp / (1.0 + $exp);
    }
}
