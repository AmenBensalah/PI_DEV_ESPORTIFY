<?php

namespace App\Controller;

use App\Entity\Equipe;
use App\Service\TeamReportService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/equipe')]
class TeamReportController extends AbstractController
{
    #[Route('/{id}/signalement', name: 'app_equipes_report', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function report(Equipe $equipe, Request $request, TeamReportService $teamReportService): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        if (!$equipe->isActive() && !$this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('error', 'Cette équipe est déjà suspendue.');
            return $this->redirectToRoute('app_equipes_index');
        }

        $session = $request->getSession();
        $isManagerOfTeam = $this->isGranted('ROLE_MANAGER')
            && $session
            && $session->get('my_team_id') == $equipe->getId();

        if ($isManagerOfTeam) {
            $this->addFlash('error', 'Vous ne pouvez pas signaler votre propre équipe.');
            return $this->redirectToRoute('app_equipes_show', ['id' => $equipe->getId()]);
        }

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('report_team_' . $equipe->getId(), (string) $request->request->get('_token'))) {
                $this->addFlash('error', 'Jeton invalide, veuillez réessayer.');
                return $this->redirectToRoute('app_equipes_report', ['id' => $equipe->getId()]);
            }

            $reason = trim((string) $request->request->get('reason'));
            if (mb_strlen($reason) < 10) {
                $this->addFlash('error', 'Merci de décrire le problème (min. 10 caractères).');
                return $this->render('equipes/report.html.twig', [
                    'equipe' => $equipe,
                    'reason' => $reason,
                ]);
            }

            if (!$teamReportService->canReport($equipe, $user)) {
                $this->addFlash('error', 'Vous avez déjà signalé cette équipe récemment.');
                return $this->redirectToRoute('app_equipes_show', ['id' => $equipe->getId()]);
            }

            $count = $teamReportService->createReport($equipe, $user, $reason);

            if (!$equipe->isActive()) {
                $this->addFlash('success', 'Votre signalement a été pris en compte. L\'équipe a été suspendue automatiquement.');
            } else {
                $this->addFlash('success', 'Votre signalement a été envoyé. Total récent: ' . $count . '.');
            }

            return $this->redirectToRoute('app_equipes_show', ['id' => $equipe->getId()]);
        }

        return $this->render('equipes/report.html.twig', [
            'equipe' => $equipe,
        ]);
    }
}
