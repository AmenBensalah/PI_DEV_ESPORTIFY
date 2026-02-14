<?php

namespace App\Service;

use App\Entity\Candidature;
use App\Entity\Equipe;

class TeamBalanceService
{
    /**
     * @param Candidature[] $candidatures
     * @return array<string,mixed>
     */
    public function analyze(Equipe $equipe, array $candidatures): array
    {
        $gameType = $this->detectGameType($equipe);
        $roleSet = $this->getRoleSet($gameType);
        $roleCounts = [
            'support' => 0,
            'tank' => 0,
            'dps' => 0,
            'sniper' => 0,
            'shotcaller' => 0,
            'flex' => 0,
            'entry' => 0,
            'initiator' => 0,
            'controller' => 0,
            'sentinel' => 0,
            'top' => 0,
            'jungle' => 0,
            'mid' => 0,
            'adc' => 0,
            'unknown' => 0,
        ];

        $total = 0;
        foreach ($candidatures as $candidature) {
            if (!$this->isAccepted($candidature)) {
                continue;
            }
            $total++;
            $role = $this->detectRole($candidature);
            $roleCounts[$role] = ($roleCounts[$role] ?? 0) + 1;
        }

        $dominant = null;
        $dominantRatio = 0.0;
        foreach ($roleCounts as $role => $count) {
            if ($total > 0) {
                $ratio = $count / $total;
                if ($ratio > $dominantRatio) {
                    $dominantRatio = $ratio;
                    $dominant = $role;
                }
            }
        }

        $imbalanced = $total >= 3 && $dominantRatio >= 0.6;

        $coreRoles = $roleSet;
        $missing = [];
        foreach ($coreRoles as $role) {
            if (($roleCounts[$role] ?? 0) === 0) {
                $missing[] = $role;
            }
        }

        $balanceScore = $this->computeBalanceScore($dominantRatio, count($missing), count($coreRoles), $total);

        return [
            'total' => $total,
            'roleCounts' => $roleCounts,
            'dominantRole' => $dominant,
            'dominantRatio' => $dominantRatio,
            'imbalanced' => $imbalanced,
            'missingRoles' => $missing,
            'balanceScore' => $balanceScore,
            'gameType' => $gameType,
            'coreRoles' => $coreRoles,
        ];
    }

    private function isAccepted(Candidature $candidature): bool
    {
        $status = (string) $candidature->getStatut();
        return $status === 'Accepté' || $status === 'AcceptÃ©';
    }

    private function detectRole(Candidature $candidature): string
    {
        $text = mb_strtolower(
            trim((string) $candidature->getPlayStyle().' '.(string) $candidature->getReason().' '.(string) $candidature->getMotivation())
        );

        if ($this->hasAny($text, ['initiator', 'entry', 'duelist', 'duellist'])) {
            return $this->hasAny($text, ['initiator']) ? 'initiator' : 'entry';
        }
        if ($this->hasAny($text, ['controller', 'smoke', 'smoker'])) {
            return 'controller';
        }
        if ($this->hasAny($text, ['sentinel', 'anchor', 'defense'])) {
            return 'sentinel';
        }
        if ($this->hasAny($text, ['top', 'toplane'])) {
            return 'top';
        }
        if ($this->hasAny($text, ['jungle', 'jungler'])) {
            return 'jungle';
        }
        if ($this->hasAny($text, ['mid', 'midlane'])) {
            return 'mid';
        }
        if ($this->hasAny($text, ['adc', 'carry', 'bot', 'botlane'])) {
            return 'adc';
        }
        if ($this->hasAny($text, ['support', 'heal', 'soin', 'healer'])) {
            return 'support';
        }
        if ($this->hasAny($text, ['tank', 'defense', 'défens', 'defens', 'front'])) {
            return 'tank';
        }
        if ($this->hasAny($text, ['sniper', 'snipe', 'long range', 'distance'])) {
            return 'sniper';
        }
        if ($this->hasAny($text, ['shotcaller', 'leader', 'capitaine', 'strat', 'strategy', 'tactique'])) {
            return 'shotcaller';
        }
        if ($this->hasAny($text, ['flex', 'polyvalent', 'adaptable'])) {
            return 'flex';
        }
        if ($this->hasAny($text, ['dps', 'carry', 'damage', 'attaque', 'agressif', 'aggressive'])) {
            return 'dps';
        }

        return 'unknown';
    }

    private function detectGameType(Equipe $equipe): string
    {
        $hay = mb_strtolower(trim((string) ($equipe->getDescription() ?? '').' '.($equipe->getTag() ?? '').' '.($equipe->getNomEquipe() ?? '')));
        if ($this->hasAny($hay, ['lol', 'league', 'dota', 'moba'])) {
            return 'moba';
        }
        if ($this->hasAny($hay, ['valorant', 'cs', 'counter', 'fps', 'apex', 'pubg'])) {
            return 'fps';
        }
        return 'generic';
    }

    /**
     * @return string[]
     */
    private function getRoleSet(string $gameType): array
    {
        return match ($gameType) {
            'fps' => ['entry', 'initiator', 'controller', 'sentinel'],
            'moba' => ['top', 'jungle', 'mid', 'adc', 'support'],
            default => ['support', 'tank', 'dps', 'flex'],
        };
    }

    private function computeBalanceScore(float $dominantRatio, int $missingCount, int $roleSlots, int $total): int
    {
        if ($total <= 0) {
            return 0;
        }
        $dominantPenalty = (int) round(max(0, ($dominantRatio - 0.4)) * 100);
        $missingPenalty = $roleSlots > 0 ? (int) round(($missingCount / $roleSlots) * 100) : 0;
        $score = 100 - (int) round(($dominantPenalty * 0.6) + ($missingPenalty * 0.4));
        if ($score < 0) {
            $score = 0;
        }
        return $score;
    }

    private function hasAny(string $text, array $tokens): bool
    {
        foreach ($tokens as $token) {
            if (str_contains($text, $token)) {
                return true;
            }
        }
        return false;
    }
}
