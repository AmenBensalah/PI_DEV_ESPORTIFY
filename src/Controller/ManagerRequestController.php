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
        // Ensure session is started for flash messages
        $request->getSession()->start();

        $user = $this->getUser();
        if (!$user) {
            $this->addFlash('error', 'Vous devez être connecté pour effectuer une demande.');
            return $this->redirectToRoute('app_login');
        }

        // Check internal status
        if ($this->isGranted('ROLE_MANAGER') || $this->isGranted('ROLE_ADMIN')) {
             $this->addFlash('info', 'Vous êtes déjà Manager ou Admin.');
             return $this->redirectToRoute('app_become_manager');
        }

        // Check if pending request exists
        $existingRequest = $repo->findOneBy(['user' => $user, 'status' => 'pending']);
        if ($existingRequest) {
            $this->addFlash('warning', 'Vous avez déjà une demande en attente.');
            return $this->redirectToRoute('app_become_manager');
        }

        // Get form data
        $nom = $request->request->get('nom');
        $experience = $request->request->get('experience');
        $motivation = $request->request->get('motivation');

        // Validation (server-side only)
        $errors = [];
        $nom = trim((string) $nom);
        $experience = trim((string) $experience);
        $motivation = trim((string) $motivation);

        if ($nom === '' || mb_strlen($nom) < 2 || mb_strlen($nom) > 100) {
            $errors[] = 'Le nom doit contenir entre 2 et 100 caractères.';
        }
        if ($experience === '' || mb_strlen($experience) < 10) {
            $errors[] = "Veuillez décrire votre expérience (min. 10 caractères).";
        }
        if ($motivation === '' || mb_strlen($motivation) < 10) {
            $errors[] = "Veuillez expliquer votre motivation (min. 10 caractères).";
        }

        if (!empty($errors)) {
            foreach ($errors as $error) {
                $this->addFlash('error', $error);
            }
            return $this->redirectToRoute('app_become_manager');
        }

        // Create Entity
        $managerRequest = new ManagerRequest();
        $managerRequest->setUser($user);
        $managerRequest->setNom($nom);
        $managerRequest->setExperience($experience);
        $managerRequest->setMotivation($motivation);
        try {
            $entityManager->persist($managerRequest);
            $entityManager->flush();
            $this->addFlash('success', 'Votre demande a été envoyée avec succès.');
        } catch (\Throwable $e) {
            $this->addFlash('error', 'Erreur lors de l\'enregistrement de la demande.');
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('app_become_manager');
    }

    // Optional: Route to show form if not embedded elsewhere
    #[Route('/devenir-manager', name: 'app_become_manager')]
    public function showForm(): Response
    {
        return $this->render('home/formmanager.html.twig');
    }
}
