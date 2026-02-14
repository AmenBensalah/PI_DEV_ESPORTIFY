<?php

namespace App\Service;

use App\Entity\Equipe;
use App\Entity\TeamReport;
use App\Entity\User;
use App\Repository\TeamReportRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;

class TeamReportService
{
    private const REPORT_THRESHOLD = 3;
    private const REPORT_WINDOW_DAYS = 7;
    private const REPORT_COOLDOWN_HOURS = 24;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private TeamReportRepository $teamReportRepository,
        private UserRepository $userRepository,
        private NotificationService $notificationService
    ) {
    }

    public function canReport(Equipe $equipe, User $reporter): bool
    {
        $since = new \DateTimeImmutable('-' . self::REPORT_COOLDOWN_HOURS . ' hours');
        return !$this->teamReportRepository->hasReporterRecent($equipe, $reporter, $since);
    }

    public function createReport(Equipe $equipe, User $reporter, string $reason): int
    {
        $report = (new TeamReport())
            ->setEquipe($equipe)
            ->setReporter($reporter)
            ->setReason($reason)
            ->setCreatedAt(new \DateTimeImmutable());

        $this->entityManager->persist($report);

        $thresholdWindow = new \DateTimeImmutable('-' . self::REPORT_WINDOW_DAYS . ' days');
        $currentCount = $this->teamReportRepository->countRecentByEquipe($equipe, $thresholdWindow) + 1;

        if ($equipe->isActive() && $currentCount >= self::REPORT_THRESHOLD) {
            $equipe->setIsActive(false);
        }

        $this->entityManager->flush();

        $this->notifyAdmins($equipe, $currentCount);

        return $currentCount;
    }

    private function notifyAdmins(Equipe $equipe, int $count): void
    {
        $admins = $this->userRepository->findAdmins();
        if ($admins === []) {
            return;
        }

        $title = 'Signalements équipe : ' . $equipe->getNomEquipe();
        $message = 'Cette équipe a été signalée (' . $count . ' signalement(s) récents).';
        $link = '/admin/equipes';

        $this->notificationService->notifyUsers($admins, $title, $message, $link, 'team_report');
    }
}
