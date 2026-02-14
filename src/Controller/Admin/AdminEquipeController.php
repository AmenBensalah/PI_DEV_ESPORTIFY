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
    public function index(Request $request, EquipeRepository $equipeRepository, TeamReportRepository $teamReportRepository): Response
    {
        $query = $request->query->get('q');
        $region = $request->query->get('region');
        $visibility = $request->query->get('visibility');
        $sort = $request->query->get('sort', 'id');
        $direction = $request->query->get('direction', 'DESC');

        $equipes = $equipeRepository->searchAndSort($query, $region, $visibility, $sort, $direction);
        $equipeIds = array_map(static fn (Equipe $e) => $e->getId(), $equipes);
        $reportCounts = $teamReportRepository->countByEquipeIds($equipeIds);

        return $this->render('admin/equipes/index.html.twig', [
            'equipes' => $equipes,
            'reportCounts' => $reportCounts,
            'currentQuery' => $query,
            'currentRegion' => $region,
            'currentVisibility' => $visibility,
            'currentSort' => $sort,
            'currentDirection' => $direction
        ]);
    }

    #[Route('/{id}', name: 'admin_equipes_delete', methods: ['POST'])]
    public function delete(Request $request, Equipe $equipe, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$equipe->getId(), $request->request->get('_token'))) {
            $entityManager->remove($equipe);
            $entityManager->flush();
            $this->addFlash('success', 'L\'équipe a été supprimée avec succès.');
        }

        return $this->redirectToRoute('admin_equipes');
    }

    #[Route('/{id}/toggle', name: 'admin_equipes_toggle', methods: ['POST'])]
    public function toggleStatus(
        Request $request, 
        Equipe $equipe, 
        EntityManagerInterface $entityManager,
        \App\Service\BrevoMailer $mailer,
        EquipeRepository $equipeRepository
    ): Response
    {
        if ($this->isCsrfTokenValid('toggle'.$equipe->getId(), $request->request->get('_token'))) {
            $reason = $request->request->get('reason');
            $wasActive = $equipe->isActive();
            $equipe->setIsActive(!$wasActive);
            $entityManager->flush();
            
            if ($wasActive && !$equipe->isActive()) {
                // Team just got suspended
                $totalTeams = count($equipeRepository->findAll());
                $members = $equipe->getMembres();
                
                foreach ($members as $member) {
                    if ($member->getEmail()) {
                        $mailer->sendTeamSuspensionEmail(
                            $member->getEmail(),
                            $equipe->getNomEquipe(),
                            $reason,
                            $totalTeams
                        );
                    }
                }
                $message = sprintf('Équipe suspendue. %d email(s) envoyé(s) aux membres.', count($members));
            } else {
                $message = 'Équipe réactivée.';
            }
            
            $this->addFlash('success', $message);
        }

        return $this->redirectToRoute('admin_equipes');
    }
}
