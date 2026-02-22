<?php

namespace App\Service;

use App\Entity\Equipe;

class TeamMatchService
{
    /**
     * @param array<string,string> $prefs
     * @param Equipe[] $teams
     * @return array<int,array<string,mixed>>
     */
    public function rankTeams(array $prefs, array $teams): array
    {
        $ranked = [];
        $maxScore = 75; // region(25) + level(20) + game(12) + style(8) + goals(10)
        foreach ($teams as $team) {
            if (!$team->isActive()) {
                continue;
            }
            [$score, $reasons] = $this->scoreTeam($prefs, $team);
            $compatibility = (int) round(($score / $maxScore) * 100);
            if ($compatibility < 0) {
                $compatibility = 0;
            }
            if ($compatibility > 100) {
                $compatibility = 100;
            }
            $ranked[] = [
                'team' => $team,
                'score' => $score,
                'compatibility' => $compatibility,
                'reasons' => $reasons,
            ];
        }

        usort($ranked, static fn ($a, $b) => $b['score'] <=> $a['score']);

        return $ranked;
    }

    /**
     * @param array<string,string> $prefs
     * @return array{0:int,1:string[]}
     */
    private function scoreTeam(array $prefs, Equipe $team): array
    {
        $score = 0;
        $reasons = [];

        $prefRegion = trim((string) ($prefs['region'] ?? ''));
        if ($prefRegion !== '' && $team->getRegion() && strcasecmp($prefRegion, $team->getRegion()) === 0) {
            $score += 25;
            $reasons[] = 'Même région';
        }

        $prefLevel = $this->mapLevel($prefs['niveau'] ?? '');
        $teamLevel = $this->mapClassement($team->getClassement() ?? '');
        if ($prefLevel !== null && $teamLevel !== null) {
            $diff = abs($prefLevel - $teamLevel);
            if ($diff <= 1) {
                $score += 20;
                $reasons[] = 'Niveau proche du classement';
            } elseif ($diff <= 2) {
                $score += 10;
                $reasons[] = 'Niveau compatible';
            }
        }

        $game = mb_strtolower(trim((string) ($prefs['game'] ?? '')));
        if ($game !== '') {
            $hay = mb_strtolower((string) ($team->getDescription() ?? '') . ' ' . ($team->getTag() ?? '') . ' ' . ($team->getNomEquipe() ?? ''));
            if (str_contains($hay, $game)) {
                $score += 12;
                $reasons[] = 'Jeu mentionné dans l’équipe';
            }
        }

        $playStyle = mb_strtolower(trim((string) ($prefs['play_style'] ?? '')));
        if ($playStyle !== '') {
            $hay = mb_strtolower((string) ($team->getDescription() ?? '') . ' ' . ($team->getTag() ?? ''));
            if (str_contains($hay, $playStyle)) {
                $score += 8;
                $reasons[] = 'Style de jeu similaire';
            }
        }

        $goals = mb_strtolower(trim((string) ($prefs['goals'] ?? '')));
        if ($goals !== '') {
            $hay = mb_strtolower((string) ($team->getDescription() ?? '') . ' ' . ($team->getTag() ?? '') . ' ' . ($team->getNomEquipe() ?? ''));
            $tokens = preg_split('/[\s,;|\/-]+/u', $goals) ?: [];
            $hits = 0;
            foreach ($tokens as $token) {
                $token = trim($token);
                if ($token === '' || mb_strlen($token) < 4) {
                    continue;
                }
                if (str_contains($hay, $token)) {
                    $hits++;
                }
            }
            if ($hits > 0) {
                $bonus = min(10, $hits * 3);
                $score += $bonus;
                $reasons[] = 'Objectifs partages';
            }
        }

        if ($team->isIsPrivate()) {
            $score -= 5;
            $reasons[] = 'Équipe privée';
        }

        if ($score < 0) {
            $score = 0;
        }

        return [$score, $reasons];
    }

    private function mapLevel(string $level): ?int
    {
        $level = mb_strtolower(trim($level));
        return match (true) {
            str_contains($level, 'debut') => 1,
            str_contains($level, 'inter') => 2,
            str_contains($level, 'confirm') => 3,
            str_contains($level, 'expert') => 4,
            default => null,
        };
    }

    private function mapClassement(string $classement): ?int
    {
        $c = mb_strtolower(trim($classement));
        return match (true) {
            str_contains($c, 'bronze') => 1,
            str_contains($c, 'argent') => 2,
            str_contains($c, 'or') => 3,
            str_contains($c, 'platine') => 4,
            str_contains($c, 'diamant') => 5,
            str_contains($c, 'master') => 6,
            str_contains($c, 'challenger') => 7,
            default => null,
        };
    }
}
