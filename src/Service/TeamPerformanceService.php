<?php

namespace App\Service;

use App\Entity\Equipe;
use App\Repository\CandidatureRepository;

class TeamPerformanceService
{
    public function __construct(private CandidatureRepository $candidatureRepository)
    {
    }

    /**
     * @return array<string,mixed>
     */
    public function analyze(Equipe $equipe): array
    {
        $now = new \DateTimeImmutable('now');
        $last30Start = $now->modify('-30 days');
        $prev30Start = $now->modify('-60 days');

        $acceptedLast30 = $this->candidatureRepository->countAcceptedByEquipeAndRange($equipe, $last30Start, $now);
        $acceptedPrev30 = $this->candidatureRepository->countAcceptedByEquipeAndRange($equipe, $prev30Start, $last30Start);

        $totalLast30 = $this->candidatureRepository->countTotalByEquipeAndRange($equipe, $last30Start, $now);
        $totalPrev30 = $this->candidatureRepository->countTotalByEquipeAndRange($equipe, $prev30Start, $last30Start);

        $trendAccepted = $this->trendPercent($acceptedPrev30, $acceptedLast30);
        $trendTotal = $this->trendPercent($totalPrev30, $totalLast30);

        return [
            'acceptedLast30' => $acceptedLast30,
            'acceptedPrev30' => $acceptedPrev30,
            'totalLast30' => $totalLast30,
            'totalPrev30' => $totalPrev30,
            'trendAccepted' => $trendAccepted,
            'trendTotal' => $trendTotal,
        ];
    }

    private function trendPercent(int $prev, int $current): int
    {
        if ($prev <= 0) {
            return $current > 0 ? 100 : 0;
        }
        return (int) round((($current - $prev) / $prev) * 100);
    }
}
