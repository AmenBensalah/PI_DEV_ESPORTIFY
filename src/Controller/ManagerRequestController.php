<?php

namespace App\Controller;

use App\Entity\ManagerRequest;
use App\Repository\ManagerRequestRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class ManagerRequestController extends AbstractController
{
    #[Route('/manager-request-submit', name: 'app_manager_request_submit', methods: ['POST'])]
    public function submit(Request $request, EntityManagerInterface $entityManager, ManagerRequestRepository $repo): Response
    {
        $user = $this->getUser();
        if (!$user) {
            $this->addFlash('error', 'Vous devez être connecté pour effectuer une demande.');
            return $this->redirectToRoute('app_login');
        }

        // Check internal status
        if ($this->isGranted('ROLE_MANAGER') || $this->isGranted('ROLE_ADMIN')) {
             $this->addFlash('info', 'Vous êtes déjà Manager ou Admin.');
             return $this->redirectToRoute('app_home');
        }

        // Check if pending request exists
        $existingRequest = $repo->findOneBy(['user' => $user, 'status' => 'pending']);
        if ($existingRequest) {
            $this->addFlash('warning', 'Vous avez déjà une demande en attente.');
            return $this->redirectToRoute('app_home');
        }

        // Get form data
        $nom = $request->request->get('nom');
        $experience = $request->request->get('experience');
        $motivation = $request->request->get('motivation');

        // Validation
        if (empty($nom) || empty($experience) || empty($motivation)) {
            $this->addFlash('error', 'Veuillez remplir tous les champs.');
            return $this->redirectToRoute('app_home'); // Fallback, assume form is on home or linked from there
        }

        // Create Entity
        $managerRequest = new ManagerRequest();
        $managerRequest->setUser($user);
        $managerRequest->setNom($nom);
        $managerRequest->setExperience($experience);
        $managerRequest->setMotivation($motivation);
        
        $entityManager->persist($managerRequest);
        $entityManager->flush();

        $this->addFlash('success', 'Votre demande a été envoyée avec succès.');
        return $this->redirectToRoute('app_home');
    }

    // Optional: Route to show form if not embedded elsewhere
    #[Route('/devenir-manager', name: 'app_become_manager')]
    public function showForm(): Response
    {
        return $this->render('home/formmanager.html.twig');
    }
}
