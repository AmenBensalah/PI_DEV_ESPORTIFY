<?php

namespace App\Controller;

use App\Entity\Recrutement;
use App\Form\RecrutementType;
use App\Repository\RecrutementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/recrutements')]
final class RecrutementsController extends AbstractController
{
    #[Route(name: 'app_recrutements_index', methods: ['GET'])]
    public function index(RecrutementRepository $recrutementRepository): Response
    {
        return $this->render('recrutements/index.html.twig', [
            'recrutements' => $recrutementRepository->findAll(),
        ]);
    }

    #[Route('/manage/{id}', name: 'app_recrutements_manage', defaults: ['id' => null], methods: ['GET'])]
    public function manage(?\App\Entity\Equipe $equipe, \App\Repository\CandidatureRepository $candidatureRepository, \App\Repository\EquipeRepository $equipeRepository, Request $request): Response
    {
        // Si une équipe est passée en paramètre (via Admin/Dev Mode), on l'utilise directement
        if (!$equipe) {
            // Sinon on regarde la session
            $session = $request->getSession();
            $teamId = $session->get('my_team_id');
            
            if ($teamId) {
                $equipe = $equipeRepository->find($teamId);
            }
        }

        // DEV MODE: Allow access if we have an equipe, regardless of ownership
        if (!$equipe) {
             $this->addFlash('error', 'Aucune équipe sélectionnée.');
             return $this->redirectToRoute('app_equipes_index');
        }

        // Fetch candidatures for this team
        $candidatures = $candidatureRepository->findBy(['equipe' => $equipe], ['dateCandidature' => 'DESC']);
        
        // Count stats
        $pendingCount = 0;
        $acceptedCount = 0;
        $refusedCount = 0;
        
        foreach ($candidatures as $candidature) {
            switch ($candidature->getStatut()) {
                case 'En attente':
                    $pendingCount++;
                    break;
                case 'Accepté':
                    $acceptedCount++;
                    break;
                case 'Refusé':
                    $refusedCount++;
                    break;
            }
        }
        
        return $this->render('recrutements/manage.html.twig', [
            'candidatures' => $candidatures,
            'equipe' => $equipe,
            'pendingCount' => $pendingCount,
            'acceptedCount' => $acceptedCount,
            'refusedCount' => $refusedCount,
        ]);
    }

    #[Route('/{id}/accept', name: 'app_recrutements_accept', methods: ['POST'])]
    public function accept(Request $request, \App\Entity\Candidature $candidature, EntityManagerInterface $entityManager): Response
    {
        // SECURITY CHECK: Only Manager of this team OR Admin
        $isAdmin = $this->isGranted('ROLE_ADMIN');
        $isManager = $this->getUser() && $request->getSession()->get('my_team_id') == $candidature->getEquipe()->getId();
        
        // BYPASS FOR DEV
        if (!$isAdmin && !$isManager && false) {
             throw $this->createAccessDeniedException('Vous n\'avez pas accès à cette action.');
        }

        $candidature->setStatut('Accepté');
        $entityManager->flush();
        
        $this->addFlash('success', 'Candidature acceptée avec succès !');
        return $this->redirectToRoute('app_recrutements_manage', ['id' => $candidature->getEquipe()->getId()], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/refuse', name: 'app_recrutements_refuse', methods: ['POST'])]
    public function refuse(Request $request, \App\Entity\Candidature $candidature, EntityManagerInterface $entityManager): Response
    {
        // BYPASS FOR DEV
        if (!$isAdmin && !$isManager && false) {
             throw $this->createAccessDeniedException('Vous n\'avez pas accès à cette action.');
        }

        $candidature->setStatut('Refusé');
        $entityManager->flush();
        
        $this->addFlash('success', 'Candidature refusée.');
        return $this->redirectToRoute('app_recrutements_manage', ['id' => $candidature->getEquipe()->getId()], Response::HTTP_SEE_OTHER);
    }
}
