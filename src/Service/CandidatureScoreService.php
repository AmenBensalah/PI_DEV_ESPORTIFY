<?php

namespace App\Service;

use App\Entity\Candidature;
use App\Entity\Equipe;
use Doctrine\ORM\EntityNotFoundException;

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
                $reasons[] = 'Niveau eloigne';
            }
        }

        $reasonLen = mb_strlen((string) $candidature->getReason());
        if ($reasonLen >= 120) {
            $score += 18;
            $reasons[] = 'Motivation detaillee';
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
            $reasons[] = 'Style de jeu mentionne';
        }

        $motivationLen = mb_strlen((string) $candidature->getMotivation());
        if ($motivationLen >= 40) {
            $score += 6;
            $reasons[] = 'Presentation soignee';
        }

        $teamRegion = mb_strtolower(trim((string) $equipe->getRegion()));
        $candidateRegion = mb_strtolower(trim((string) $candidature->getRegion()));
        if ($teamRegion !== '' && $candidateRegion !== '') {
            if ($candidateRegion === $teamRegion || str_contains($candidateRegion, $teamRegion) || str_contains($teamRegion, $candidateRegion)) {
                $score += 20;
                $reasons[] = 'Region identique';
            } else {
                $score += 3;
                $reasons[] = 'Region differente';
            }
        }

        $availability = mb_strtolower(trim((string) $candidature->getDisponibilite()));
        if ($availability !== '') {
            if (str_contains($availability, 'elev') || str_contains($availability, 'high')) {
                $score += 25;
                $reasons[] = 'Disponibilite elevee';
            } elseif (str_contains($availability, 'moy')) {
                $score += 15;
                $reasons[] = 'Disponibilite moyenne';
            } elseif (str_contains($availability, 'faib') || str_contains($availability, 'low')) {
                $score += 5;
                $reasons[] = 'Disponibilite faible';
            }
        }

        $reasonAi = $candidature->getReasonAiScore();
        if ($reasonAi !== null) {
            $bonus = (int) round(max(0, min(100, $reasonAi)) * 0.12);
            $score += $bonus;
            $reasons[] = 'Bonus IA raison +' . $bonus;
        }

        try {
            $user = $candidature->getUser();
            if ($user && $user->getPseudo()) {
                $score += 3;
                $reasons[] = 'Profil renseigne';
            }
        } catch (EntityNotFoundException) {
            // Broken relation (user deleted): ignore profile bonus.
        } catch (\Throwable) {
            // Keep scoring resilient.
        }

        if ($score < 0) {
            $score = 0;
        }
        if ($score > 100) {
            $score = 100;
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
