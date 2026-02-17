<?php

namespace App\Service;

use App\Entity\Tournoi;
use App\Entity\TournoiMatch;
use App\Entity\User;
use App\Repository\CandidatureRepository;
use App\Repository\EquipeRepository;
use App\Repository\TournoiMatchRepository;
use Doctrine\ORM\EntityManagerInterface;

class UserPerformanceAIService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private TournoiMatchRepository $tournoiMatchRepository,
        private EquipeRepository $equipeRepository,
        private CandidatureRepository $candidatureRepository,
        private UserPerformanceMLService $userPerformanceMLService
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function buildReport(User $user): array
    {
        $events = $this->getUserMatchEvents($user);
        $tournoisPlayed = $this->countUniqueTournois($events);

        $matchesPlayed = 0;
        $wins = 0;
        $draws = 0;
        $losses = 0;
        $totalPoints = 0;

        $placements = [
            'first' => 0,
            'second' => 0,
            'third' => 0,
        ];

        $quality = [
            'matchedByPlayerLink' => 0,
            'matchedByNameAlias' => 0,
            'matchedByPlacement' => 0,
            'ambiguousSideMatches' => 0,
        ];

        $gameStats = [];
        $recentForm = [];

        foreach ($events as $event) {
            $typeGame = (string) ($event['typeGame'] ?? 'Unknown');

            $matchesPlayed++;
            $totalPoints += (int) ($event['points'] ?? 0);

            if (!isset($gameStats[$typeGame])) {
                $gameStats[$typeGame] = [
                    'played' => 0,
                    'wins' => 0,
                    'draws' => 0,
                    'losses' => 0,
                    'points' => 0,
                ];
            }

            $gameStats[$typeGame]['played']++;
            $gameStats[$typeGame]['points'] += (int) ($event['points'] ?? 0);

            $matchedBy = (string) ($event['matchedBy'] ?? 'none');
            if ($matchedBy === 'player_link') {
                $quality['matchedByPlayerLink']++;
            } elseif ($matchedBy === 'name_alias') {
                $quality['matchedByNameAlias']++;
            } elseif ($matchedBy === 'ambiguous') {
                $quality['ambiguousSideMatches']++;
            } elseif ($matchedBy === 'placement') {
                $quality['matchedByPlacement']++;
            }

            $placement = (string) ($event['placement'] ?? '');
            if ($placement !== '' && isset($placements[$placement])) {
                $placements[$placement]++;
                $quality['matchedByPlacement']++;
            }

            $result = $event['result'] ?? null;
            if ($result === 'W') {
                $wins++;
                $gameStats[$typeGame]['wins']++;
            } elseif ($result === 'D') {
                $draws++;
                $gameStats[$typeGame]['draws']++;
            } elseif ($result === 'L') {
                $losses++;
                $gameStats[$typeGame]['losses']++;
            }

            if (count($recentForm) < 5 && ($result === 'W' || $result === 'D' || $result === 'L')) {
                $recentForm[] = $result;
            }
        }

        $winRate = $matchesPlayed > 0 ? round(($wins / $matchesPlayed) * 100, 1) : 0.0;
        $averagePoints = $matchesPlayed > 0 ? round($totalPoints / $matchesPlayed, 2) : 0.0;

        $bestGameType = null;
        $bestGameWinRate = 0.0;
        foreach ($gameStats as $gameType => &$stats) {
            $denominator = (int) $stats['played'];
            $stats['winRate'] = $denominator > 0 ? round((((int) $stats['wins']) / $denominator) * 100, 1) : 0.0;
            $stats['avgPoints'] = $denominator > 0 ? round((((int) $stats['points']) / $denominator), 2) : 0.0;
            if ($denominator > 0 && $stats['winRate'] >= $bestGameWinRate) {
                $bestGameWinRate = $stats['winRate'];
                $bestGameType = $gameType;
            }
        }
        unset($stats);

        $trend = $this->computeTrend($recentForm);
        $confidence = $this->computeConfidence($matchesPlayed);

        $recentMatches = array_map(
            function (array $event): array {
                return [
                    'date' => (string) ($event['date'] ?? ''),
                    'tournoi' => (string) ($event['tournoi'] ?? 'Tournoi'),
                    'typeGame' => (string) ($event['typeGame'] ?? 'Unknown'),
                    'typeTournoi' => (string) ($event['typeTournoi'] ?? 'unknown'),
                    'result' => $event['result'] ?? '-',
                    'points' => (int) ($event['points'] ?? 0),
                    'placement' => $event['placement'] ?? null,
                    'matchedBy' => (string) ($event['matchedBy'] ?? 'none'),
                ];
            },
            array_slice($events, 0, 12)
        );

        $mlPrediction = null;
        if ($user->getId() !== null) {
            $mlPrediction = $this->userPerformanceMLService->getPredictionForUser($user->getId());
        }

        return [
            'generatedAt' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
            'summary' => [
                'tournoisPlayed' => $tournoisPlayed,
                'matchesPlayed' => $matchesPlayed,
                'wins' => $wins,
                'draws' => $draws,
                'losses' => $losses,
                'winRate' => $winRate,
                'totalPoints' => $totalPoints,
                'averagePointsPerMatch' => $averagePoints,
                'bestGameType' => $bestGameType,
                'bestGameWinRate' => $bestGameWinRate,
            ],
            'placements' => $placements,
            'perGame' => $gameStats,
            'recentForm' => $recentForm,
            'recentMatches' => $recentMatches,
            'trend' => $trend,
            'confidence' => $confidence,
            'dataQuality' => $quality,
            'aiInsight' => $this->buildInsight(
                $matchesPlayed,
                $winRate,
                $bestGameType,
                $trend,
                $placements,
                $confidence
            ),
            'mlPrediction' => $mlPrediction,
            'mlModelInfo' => $this->userPerformanceMLService->getModelInfo(),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getUserMatchEvents(User $user, bool $ascending = false): array
    {
        $tournois = $this->findParticipatedTournois($user);
        $identityAliases = $this->buildIdentityAliases($user);
        $teamAliases = $this->buildTeamAliases($user);

        $events = [];
        foreach ($tournois as $tournoi) {
            $matches = $this->tournoiMatchRepository->findByTournoiOrdered($tournoi);
            foreach ($matches as $match) {
                if ($match->getStatus() !== 'played') {
                    continue;
                }

                $side = $this->extractSideResult($match, $user, $identityAliases, $teamAliases);
                $placement = $this->extractPlacementResult($match, $user);

                if (!$side['participated'] && !$placement['participated']) {
                    continue;
                }

                $timestamp = $this->extractMatchTimestamp($match);
                $date = date('Y-m-d H:i', $timestamp);
                $result = $side['result'] ?? $placement['result'] ?? null;
                $points = (int) $side['points'] + (int) $placement['points'];

                $matchedBy = $side['matchedBy'];
                if (($matchedBy === 'none' || $matchedBy === '') && $placement['participated']) {
                    $matchedBy = 'placement';
                }

                $events[] = [
                    'tournoiId' => $tournoi->getIdTournoi(),
                    'tournoi' => (string) ($tournoi->getName() ?? 'Tournoi'),
                    'typeGame' => (string) ($tournoi->getTypeGame() ?? 'Unknown'),
                    'typeTournoi' => (string) ($tournoi->getTypeTournoi() ?? 'unknown'),
                    'timestamp' => $timestamp,
                    'date' => $date,
                    'result' => $result,
                    'points' => $points,
                    'sidePoints' => (int) $side['points'],
                    'placementPoints' => (int) $placement['points'],
                    'placement' => $placement['placement'],
                    'matchedBy' => $matchedBy,
                ];
            }
        }

        usort($events, static function (array $a, array $b) use ($ascending): int {
            if ($ascending) {
                return ((int) ($a['timestamp'] ?? 0)) <=> ((int) ($b['timestamp'] ?? 0));
            }

            return ((int) ($b['timestamp'] ?? 0)) <=> ((int) ($a['timestamp'] ?? 0));
        });

        return $events;
    }

    /**
     * @param array<int, array<string, mixed>> $events
     */
    private function countUniqueTournois(array $events): int
    {
        $ids = [];
        foreach ($events as $event) {
            $id = $event['tournoiId'] ?? null;
            if ($id !== null) {
                $ids[(string) $id] = true;
            }
        }

        return count($ids);
    }

    /**
     * @return Tournoi[]
     */
    private function findParticipatedTournois(User $user): array
    {
        return $this->entityManager
            ->createQueryBuilder()
            ->select('t')
            ->from(Tournoi::class, 't')
            ->innerJoin('t.participants', 'p')
            ->andWhere('p = :user')
            ->setParameter('user', $user)
            ->orderBy('t.startDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return array<string, true>
     */
    private function buildIdentityAliases(User $user): array
    {
        $aliases = [];
        $this->registerAlias($aliases, $user->getNom());
        $this->registerAlias($aliases, $user->getPseudo());
        $this->registerAlias($aliases, $user->getEmail());

        return $aliases;
    }

    /**
     * @return array<string, true>
     */
    private function buildTeamAliases(User $user): array
    {
        $aliases = [];

        $managedTeam = $this->equipeRepository->findOneBy(['manager' => $user]);
        if ($managedTeam && $managedTeam->getNomEquipe()) {
            $this->registerAlias($aliases, (string) $managedTeam->getNomEquipe());
        }

        $membership = $this->candidatureRepository->findAcceptedMembershipByUser($user);
        if ($membership && $membership->getEquipe() && $membership->getEquipe()->getNomEquipe()) {
            $this->registerAlias($aliases, (string) $membership->getEquipe()->getNomEquipe());
        }

        return $aliases;
    }

    /**
     * @param array<string, true> $identityAliases
     * @param array<string, true> $teamAliases
     * @return array{participated: bool, result: ?string, points: int, matchedBy: string}
     */
    private function extractSideResult(
        TournoiMatch $match,
        User $user,
        array $identityAliases,
        array $teamAliases
    ): array {
        $scoreA = $match->getScoreA();
        $scoreB = $match->getScoreB();

        $homeAliases = $identityAliases + $teamAliases;
        $awayAliases = $identityAliases + $teamAliases;

        $homeKey = $this->normalizeName($match->getHomeName());
        $awayKey = $this->normalizeName($match->getAwayName());

        $isHome = false;
        $isAway = false;
        $matchedBy = 'none';

        if ($match->getPlayerA() && $match->getPlayerA()->getId() === $user->getId()) {
            $isHome = true;
            $matchedBy = 'player_link';
        } elseif ($homeKey !== '' && isset($homeAliases[$homeKey])) {
            $isHome = true;
            $matchedBy = 'name_alias';
        }

        if ($match->getPlayerB() && $match->getPlayerB()->getId() === $user->getId()) {
            $isAway = true;
            if ($matchedBy === 'none') {
                $matchedBy = 'player_link';
            }
        } elseif ($awayKey !== '' && isset($awayAliases[$awayKey])) {
            $isAway = true;
            if ($matchedBy === 'none') {
                $matchedBy = 'name_alias';
            }
        }

        if (!$isHome && !$isAway) {
            return [
                'participated' => false,
                'result' => null,
                'points' => 0,
                'matchedBy' => 'none',
            ];
        }

        if ($isHome && $isAway) {
            return [
                'participated' => true,
                'result' => null,
                'points' => 0,
                'matchedBy' => 'ambiguous',
            ];
        }

        if ($scoreA === null || $scoreB === null) {
            return [
                'participated' => true,
                'result' => null,
                'points' => 0,
                'matchedBy' => $matchedBy,
            ];
        }

        if ($isHome) {
            $points = (int) $scoreA;
            $against = (int) $scoreB;
        } else {
            $points = (int) $scoreB;
            $against = (int) $scoreA;
        }

        $result = $points > $against ? 'W' : ($points === $against ? 'D' : 'L');

        return [
            'participated' => true,
            'result' => $result,
            'points' => $points,
            'matchedBy' => $matchedBy,
        ];
    }

    /**
     * @return array{participated: bool, result: ?string, points: int, placement: ?string}
     */
    private function extractPlacementResult(TournoiMatch $match, User $user): array
    {
        $points = 0;
        $placement = null;

        foreach ($match->getParticipantResults() as $participantResult) {
            if ($participantResult->getParticipant()?->getId() !== $user->getId()) {
                continue;
            }

            $points += (int) $participantResult->getPoints();
            $placement = strtolower(trim((string) $participantResult->getPlacement()));
        }

        if ($placement === null) {
            return [
                'participated' => false,
                'result' => null,
                'points' => 0,
                'placement' => null,
            ];
        }

        $result = $placement === 'first' ? 'W' : 'L';

        return [
            'participated' => true,
            'result' => $result,
            'points' => $points,
            'placement' => $placement,
        ];
    }

    private function extractMatchTimestamp(TournoiMatch $match): int
    {
        if ($match->getScheduledAt() !== null) {
            return $match->getScheduledAt()->getTimestamp();
        }

        return $match->getCreatedAt()->getTimestamp();
    }

    /**
     * @param string[] $recentForm
     */
    private function computeTrend(array $recentForm): string
    {
        if ($recentForm === []) {
            return 'insufficient_data';
        }

        $score = 0;
        foreach ($recentForm as $result) {
            if ($result === 'W') {
                $score += 1;
            } elseif ($result === 'L') {
                $score -= 1;
            }
        }

        if ($score >= 2) {
            return 'up';
        }
        if ($score <= -2) {
            return 'down';
        }

        return 'stable';
    }

    private function computeConfidence(int $matchesPlayed): string
    {
        if ($matchesPlayed >= 20) {
            return 'high';
        }
        if ($matchesPlayed >= 8) {
            return 'medium';
        }

        return 'low';
    }

    /**
     * @param array<string, int> $placements
     */
    private function buildInsight(
        int $matchesPlayed,
        float $winRate,
        ?string $bestGameType,
        string $trend,
        array $placements,
        string $confidence
    ): string {
        if ($matchesPlayed === 0) {
            return 'Pas assez de matchs joues pour generer une analyse IA fiable.';
        }

        $parts = [];
        if ($winRate >= 65) {
            $parts[] = 'Ton niveau est solide avec un taux de victoire eleve.';
        } elseif ($winRate >= 45) {
            $parts[] = 'Ton profil est equilibre mais il reste une marge de progression.';
        } else {
            $parts[] = 'Le taux de victoire est encore faible, il faut stabiliser les performances.';
        }

        if ($bestGameType !== null) {
            $parts[] = 'Ton meilleur rendement actuel est sur le type de jeu ' . $bestGameType . '.';
        }

        if ($trend === 'up') {
            $parts[] = 'La dynamique recente est positive.';
        } elseif ($trend === 'down') {
            $parts[] = 'La dynamique recente est en baisse.';
        } else {
            $parts[] = 'La dynamique recente est plutot stable.';
        }

        if (($placements['first'] ?? 0) > 0) {
            $parts[] = 'Tu as deja atteint la premiere place sur certains matchs.';
        }

        if ($confidence === 'low') {
            $parts[] = 'Fiabilite faible: il faut plus de matchs pour une IA plus precise.';
        }

        return implode(' ', $parts);
    }

    /**
     * @param array<string, true> $aliases
     */
    private function registerAlias(array &$aliases, ?string $value): void
    {
        $normalized = $this->normalizeName($value);
        if ($normalized !== '') {
            $aliases[$normalized] = true;
        }
    }

    private function normalizeName(?string $value): string
    {
        $trimmed = trim((string) $value);
        if ($trimmed === '') {
            return '';
        }

        return mb_strtolower((string) preg_replace('/\s+/', ' ', $trimmed));
    }
}

