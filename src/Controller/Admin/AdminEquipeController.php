<?php

namespace App\Controller\Admin;

use App\Entity\Equipe;
use App\Repository\EquipeRepository;
use App\Repository\TeamReportRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/equipes')]
#[IsGranted('ROLE_ADMIN')]
class AdminEquipeController extends AbstractController
{
    #[Route('/', name: 'admin_equipes', methods: ['GET'])]
    public function index(
        Request $request,
        EquipeRepository $equipeRepository,
        TeamReportRepository $teamReportRepository,
        EntityManagerInterface $entityManager
    ): Response
    {
        $this->reactivateExpiredSuspensions($equipeRepository, $entityManager);

        $query = $request->query->get('q');
        $region = $request->query->get('region');
        $visibility = $request->query->get('visibility');
        $sort = $request->query->get('sort', 'id');
        $direction = $request->query->get('direction', 'DESC');

        $perPage = 10;
        $page = max(1, (int) $request->query->get('page', 1));
        $queryBuilder = $equipeRepository->searchAndSortQueryBuilder($query, $region, $visibility, $sort, $direction);
        $countQueryBuilder = clone $queryBuilder;
        $totalItems = (int) $countQueryBuilder
            ->select('COUNT(e.id)')
            ->resetDQLPart('orderBy')
            ->getQuery()
            ->getSingleScalarResult();

        $pageCount = max(1, (int) ceil($totalItems / $perPage));
        $page = min($page, $pageCount);

        $equipes = (clone $queryBuilder)
            ->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults($perPage)
            ->getQuery()
            ->getResult();

        $rangeStart = max(1, $page - 2);
        $rangeEnd = min($pageCount, $rangeStart + 4);
        $rangeStart = max(1, $rangeEnd - 4);
        $pagesInRange = range($rangeStart, $rangeEnd);

        $equipeIds = [];
        foreach ($equipes as $equipe) {
            if ($equipe instanceof Equipe && $equipe->getId() !== null) {
                $equipeIds[] = $equipe->getId();
            }
        }
        $reportCounts = $teamReportRepository->countByEquipeIds($equipeIds);

        return $this->render('admin/equipes/index.html.twig', [
            'equipes' => $equipes,
            'reportCounts' => $reportCounts,
            'currentQuery' => $query,
            'currentRegion' => $region,
            'currentVisibility' => $visibility,
            'currentSort' => $sort,
            'currentDirection' => $direction,
            'pagination' => [
                'current' => $page,
                'pageCount' => $pageCount,
                'pagesInRange' => $pagesInRange,
                'previous' => $page > 1 ? $page - 1 : null,
                'next' => $page < $pageCount ? $page + 1 : null,
            ],
        ]);
    }

    #[Route('/{id}/suspend', name: 'admin_equipes_suspend', methods: ['GET', 'POST'])]
    public function suspend(
        Request $request,
        Equipe $equipe,
        EntityManagerInterface $entityManager,
        \App\Service\BrevoMailer $mailer,
        EquipeRepository $equipeRepository
    ): Response {
        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('suspend' . $equipe->getId(), (string) $request->request->get('_token'))) {
                $this->addFlash('error', 'Jeton CSRF invalide.');
                return $this->redirectToRoute('admin_equipes');
            }

            $reason = trim((string) $request->request->get('reason', ''));
            if ($reason === '') {
                $reason = "equipe suspendue par l'admin";
            }
            $durationDays = max(1, min(365, (int) $request->request->get('duration_days', 7)));
            $sendMail = (string) $request->request->get('send_mail', '0') === '1';
            $suspendedUntil = (new \DateTimeImmutable('now'))->modify('+' . $durationDays . ' days');

            $equipe->setIsActive(false);
            $equipe->setSuspensionReason($reason);
            $equipe->setSuspensionDurationDays($durationDays);
            $equipe->setSuspendedUntil($suspendedUntil);
            $entityManager->flush();

            $sentCount = 0;
            if ($sendMail) {
                $totalTeams = count($equipeRepository->findAll());
                $members = $equipe->getMembres();
                foreach ($members as $member) {
                    if ($member->getEmail()) {
                        if ($mailer->sendTeamSuspensionEmail($member->getEmail(), (string) $equipe->getNomEquipe(), $reason, $totalTeams, $suspendedUntil, $durationDays)) {
                            $sentCount++;
                        }
                    }
                }
            }

            $msg = sprintf(
                'Equipe suspendue %d jour(s), reactivation prevue le %s.',
                $durationDays,
                $suspendedUntil->format('d/m/Y H:i')
            );
            if ($sendMail) {
                $msg .= sprintf(' %d email(s) envoye(s).', $sentCount);
            }
            $this->addFlash('success', $msg);

            return $this->redirectToRoute('admin_equipes');
        }

        return $this->render('admin/equipes/suspend.html.twig', [
            'equipe' => $equipe,
            'manager' => $equipe->getManager(),
        ]);
    }

    #[Route('/{id}/toggle', name: 'admin_equipes_toggle', methods: ['POST'])]
    public function toggleStatus(Request $request, Equipe $equipe, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isCsrfTokenValid('toggle' . $equipe->getId(), (string) $request->request->get('_token'))) {
            return $this->redirectToRoute('admin_equipes');
        }

        // Manual reactivate path from admin list.
        $equipe->setIsActive(!$equipe->isActive());
        if ($equipe->isActive()) {
            $equipe->setSuspensionReason(null);
            $equipe->setSuspendedUntil(null);
            $equipe->setSuspensionDurationDays(null);
            $this->addFlash('success', 'Equipe reactivee.');
        } else {
            $equipe->setSuspensionReason("equipe suspendue par l'admin");
            $equipe->setSuspensionDurationDays(7);
            $equipe->setSuspendedUntil((new \DateTimeImmutable('now'))->modify('+7 days'));
            $this->addFlash('success', 'Equipe suspendue (mode rapide).');
        }

        $entityManager->flush();
        return $this->redirectToRoute('admin_equipes');
    }

    #[Route('/{id}', name: 'admin_equipes_delete', methods: ['POST'])]
    public function delete(Request $request, Equipe $equipe, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $equipe->getId(), (string) $request->request->get('_token'))) {
            // Safety cleanup: some DB snapshots have chat_message FK without ON DELETE CASCADE.
            try {
                $entityManager->getConnection()->executeStatement(
                    'DELETE FROM chat_message WHERE equipe_id = :teamId',
                    ['teamId' => $equipe->getId()]
                );
            } catch (\Throwable) {
                // Ignore if table does not exist in current snapshot.
            }

            $entityManager->remove($equipe);
            $entityManager->flush();
            $this->addFlash('success', "L'equipe a ete supprimee avec succes.");
        }

        return $this->redirectToRoute('admin_equipes');
    }

    private function reactivateExpiredSuspensions(EquipeRepository $equipeRepository, EntityManagerInterface $entityManager): void
    {
        $now = new \DateTimeImmutable('now');
        $changed = false;
        foreach ($equipeRepository->findAll() as $team) {
            if ($team->isActive()) {
                continue;
            }
            $until = $team->getSuspendedUntil();
            if ($until !== null && $until <= $now) {
                $team->setIsActive(true);
                $team->setSuspensionReason(null);
                $team->setSuspendedUntil(null);
                $team->setSuspensionDurationDays(null);
                $changed = true;
            }
        }

        if ($changed) {
            $entityManager->flush();
        }
    }
}
