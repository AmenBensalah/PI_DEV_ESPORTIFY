<?php

namespace App\Controller;

use App\Repository\EquipeRepository;
use App\Repository\RecrutementRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    #[Route('/home', name: 'app_home_index')]
    public function index(\Symfony\Component\HttpFoundation\Request $request, EquipeRepository $equipeRepository, \App\Repository\CandidatureRepository $candidatureRepository): Response
    {
        $session = $request->getSession();
        $user = $this->getUser();
        $isManager = false;
        $myTeam = null;

        // 1. Check if user is an ADMIN (Admins have manager rights everywhere)
        $isAdmin = $this->isGranted('ROLE_ADMIN');

        // 2. Check for Manager via Session
        $myTeamId = $session->get('my_team_id');
        if ($myTeamId) {
            $myTeam = $equipeRepository->find($myTeamId);
            $isManager = true;
        }

        // 3. Check for Member (Accepted Candidature) if not already a manager of this team
        if (!$isManager && $user) {
            $userEmail = $user->getUserIdentifier();
            $membership = $candidatureRepository->findOneBy([
                'email' => $userEmail,
                'statut' => 'Accepté'
            ]);
            
            if ($membership) {
                $myTeam = $membership->getEquipe();
                // If they are a member, they are NOT a manager (unless they are also Admin)
                $isManager = $isAdmin; 
            }
        }

        // 4. Case where user is Admin but doesn't have a team in session/membership
        // We might want to show them the first team or some specific management view, 
        // but for now, we follow the team lookup.

        return $this->render('home/index.html.twig', [
            'featuredTeams' => $equipeRepository->findBy([], ['id' => 'DESC'], 4),
            'myTeam' => $myTeam,
            'isManager' => $isManager || $isAdmin // Admins see management buttons if a team is shown
        ]);
    }

    #[Route('/manager/request', name: 'app_manager_request')]
    public function requestManager(): Response
    {
        return $this->render('home/formmanager.html.twig');
    }

    #[Route('/manager/request/submit', name: 'app_manager_request_submit', methods: ['POST'])]
    public function submitManagerRequest(Request $request): Response
    {
        // Ici, vous ajouteriez la logique pour sauvegarder la demande en base de données
        // Pour l'instant, on simule un succès et on redirige vers l'accueil avec un message flash
        
        $this->addFlash('success', 'Votre demande pour devenir manager a été envoyée avec succès !');
        return $this->redirectToRoute('app_home');
    }
}