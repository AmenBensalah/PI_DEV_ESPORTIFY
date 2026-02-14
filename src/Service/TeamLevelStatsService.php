<?php

namespace App\Service;

use App\Entity\Candidature;
use App\Entity\Equipe;

class TeamLevelStatsService
{
    /**
     * @param Candidature[] $candidatures
     * @return array<string,mixed>
     */
    public function analyze(Equipe $equipe, array $candidatures): array
    {
        $levels = [
            'Débutant' => 1,
            'Debutant' => 1,
            'Intermédiaire' => 2,
            'Intermediaire' => 2,
            'Confirmé' => 3,
            'Confirme' => 3,
            'Expert' => 4,
        ];

        $accepted = [];
        $lastAcceptedAt = null;
        foreach ($candidatures as $candidature) {
            if (!$this->isAccepted($candidature)) {
                continue;
            }
            $accepted[] = $candidature;
            $date = $candidature->getDateCandidature();
            if ($date && ($lastAcceptedAt === null || $date > $lastAcceptedAt)) {
                $lastAcceptedAt = $date;
            }
        }

        $counts = [
            'Débutant' => 0,
            'Intermédiaire' => 0,
            'Confirmé' => 0,
            'Expert' => 0,
        ];

        $sum = 0;
        $total = 0;
        foreach ($accepted as $candidature) {
            $raw = (string) $candidature->getNiveau();
            $normalized = $this->normalizeLevel($raw);
            $counts[$normalized] = ($counts[$normalized] ?? 0) + 1;
            $sum += $levels[$normalized] ?? 1;
            $total++;
        }

        $avg = $total > 0 ? $sum / $total : 0;
        $avgLabel = $this->labelFromAverage($avg);

        $strengths = [];
        $weaknesses = [];
        if ($total > 0) {
            $ratioBeginner = $counts['Débutant'] / $total;
            $ratioIntermediate = $counts['Intermédiaire'] / $total;
            $ratioConfirmed = $counts['Confirmé'] / $total;
            $ratioExpert = $counts['Expert'] / $total;

            if ($ratioConfirmed + $ratioExpert >= 0.5) {
                $strengths[] = 'Noyau expérimenté';
            }
            if ($ratioIntermediate >= 0.4) {
                $strengths[] = 'Équipe équilibrée en progression';
            }
            if ($ratioBeginner >= 0.6) {
                $weaknesses[] = 'Besoin de joueurs confirmés';
            }
            if ($ratioExpert === 0 && $ratioConfirmed <= 0.2) {
                $weaknesses[] = 'Peu de leadership compétitif';
            }
        } else {
            $weaknesses[] = 'Aucun membre accepté';
        }

        $isActive = $lastAcceptedAt ? $lastAcceptedAt >= new \DateTime('-30 days') : false;

        return [
            'total' => $total,
            'averageScore' => $avg,
            'averageLabel' => $avgLabel,
            'counts' => $counts,
            'strengths' => $strengths,
            'weaknesses' => $weaknesses,
            'lastAcceptedAt' => $lastAcceptedAt,
            'isActive' => $isActive,
        ];
    }

    private function normalizeLevel(string $level): string
    {
        $level = trim($level);
        $lower = mb_strtolower($level);
        if (str_contains($lower, 'début') || str_contains($lower, 'debut')) {
            return 'Débutant';
        }
        if (str_contains($lower, 'inter')) {
            return 'Intermédiaire';
        }
        if (str_contains($lower, 'confirm')) {
            return 'Confirmé';
        }
        if (str_contains($lower, 'expert')) {
            return 'Expert';
        }
        return 'Débutant';
    }

    private function labelFromAverage(float $avg): string
    {
        if ($avg >= 3.5) {
            return 'Expert';
        }
        if ($avg >= 2.6) {
            return 'Confirmé';
        }
        if ($avg >= 1.6) {
            return 'Intermédiaire';
        }
        return $avg > 0 ? 'Débutant' : 'N/A';
    }

    private function isAccepted(Candidature $candidature): bool
    {
        $status = (string) $candidature->getStatut();
        return $status === 'Accepté' || $status === 'AcceptÃ©';
    }
}
