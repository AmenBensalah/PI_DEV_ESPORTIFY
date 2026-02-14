<?php

namespace App\Service;

use App\Entity\Candidature;
use App\Entity\Equipe;

class CandidatureScoreService
{
    /**
     * @return array{score:int,reasons:string[]}
     */
    public function score(Candidature $candidature, Equipe $equipe): array
    {
        $score = 0;
        $reasons = [];

        $teamLevel = $this->mapClassement($equipe->getClassement() ?? '');
        $candLevel = $this->mapNiveau($candidature->getNiveau() ?? '');
        if ($teamLevel !== null && $candLevel !== null) {
            $diff = abs($teamLevel - $candLevel);
            if ($diff <= 1) {
                $score += 25;
                $reasons[] = 'Niveau proche du classement';
            } elseif ($diff <= 2) {
                $score += 12;
                $reasons[] = 'Niveau compatible';
            } else {
                $score += 2;
                $reasons[] = 'Niveau éloigné';
            }
        }

        $reasonLen = mb_strlen((string) $candidature->getReason());
        if ($reasonLen >= 120) {
            $score += 18;
            $reasons[] = 'Motivation détaillée';
        } elseif ($reasonLen >= 50) {
            $score += 10;
            $reasons[] = 'Motivation correcte';
        } else {
            $score += 2;
            $reasons[] = 'Motivation courte';
        }

        $styleLen = mb_strlen((string) $candidature->getPlayStyle());
        if ($styleLen >= 20) {
            $score += 8;
            $reasons[] = 'Style de jeu clair';
        } elseif ($styleLen >= 8) {
            $score += 4;
            $reasons[] = 'Style de jeu mentionné';
        }

        $motivationLen = mb_strlen((string) $candidature->getMotivation());
        if ($motivationLen >= 40) {
            $score += 6;
            $reasons[] = 'Présentation soignée';
        }

        $user = $candidature->getUser();
        if ($user && $user->getPseudo()) {
            $score += 3;
            $reasons[] = 'Profil renseigné';
        }

        if ($score < 0) {
            $score = 0;
        }

        return ['score' => $score, 'reasons' => $reasons];
    }

    private function mapNiveau(string $niveau): ?int
    {
        $n = mb_strtolower(trim($niveau));
        return match (true) {
            str_contains($n, 'debut') => 1,
            str_contains($n, 'inter') => 2,
            str_contains($n, 'confirm') => 3,
            str_contains($n, 'expert') => 4,
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
