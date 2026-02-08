<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserProfileFormType;
use App\Repository\CandidatureRepository;
use App\Repository\EquipeRepository;
use App\Repository\TournoiRepository;
use Doctrine\DBAL\Exception\LockWaitTimeoutException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\EntityManagerClosed;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class HomeController extends AbstractController
{
    #[Route('/home', name: 'app_home')]
    public function index(
        Request $request,
        EquipeRepository $equipeRepository,
        CandidatureRepository $candidatureRepository,
        \App\Repository\ProduitRepository $produitRepository,
        TournoiRepository $tournoiRepository
    ): Response {
        $session = $request->getSession();
        $user = $this->getUser();
        $isManager = false;
        $myTeam = null;

        // 1. Check if user is an ADMIN (Admins have manager rights everywhere)
        $isAdmin = $this->isGranted('ROLE_ADMIN');
        if ($isAdmin) {
            return $this->redirectToRoute('admin_dashboard');
        }



        // Find Team for display
        $myTeamId = $session->get('my_team_id');
        if ($myTeamId) {
            $myTeam = $equipeRepository->find($myTeamId);
        }

        return $this->render('home/index.html.twig', [
            'featuredTeams' => $equipeRepository->findBy([], ['id' => 'DESC'], 4),
            'products' => $produitRepository->findBy([], ['id' => 'DESC'], 4),
            'tournois' => $tournoiRepository->findBy([], ['startDate' => 'DESC'], 6),
            'myTeam' => $myTeam,
            'isManager' => $this->isGranted('ROLE_MANAGER') || $isAdmin,
            'isPlayer' => $this->isGranted('ROLE_JOUEUR'),
        ]);
    }

    #[Route('/manager/request/submit', name: 'app_manager_request_submit', methods: ['POST'])]
    public function submitManagerRequest(Request $request): Response
    {
        // Ici, vous ajouteriez la logique pour sauvegarder la demande en base de données
        // Pour l'instant, on simule un succès et on redirige vers l'accueil avec un message flash

        $this->addFlash('success', 'Votre demande pour devenir manager a été envoyée avec succès !');
        return $this->redirectToRoute('app_home');
    }

    #[Route('/manager/request', name: 'app_manager_request', methods: ['GET'])]
    public function requestManager(): Response
    {
        return $this->render('home/formmanager.html.twig');
    }

    #[Route('/profile', name: 'app_profile')]
    public function profile(): Response
    {
        return $this->render('home/profile.html.twig', [
            'user' => $this->getUser(),
        ]);
    }

    #[Route('/profile/edit', name: 'app_profile_edit')]
    public function editProfile(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        $user = $this->getUser();
        $form = $this->createForm(UserProfileFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $this->validateProfileForm($form, $user);

            if ($form->isValid()) {
                // Hash password if it was changed
                $plainPassword = $form->get('plainPassword')->getData();
                if ($plainPassword) {
                    $hashedPassword = $passwordHasher->hashPassword($user, $plainPassword);
                    $user->setPassword($hashedPassword);
                }

                $entityManager->flush();

                return $this->redirectToRoute('app_profile');
            }
        }

        return $this->render('home/edit_profile.html.twig', [
            'form' => $form,
        ]);
    }

    private function validateProfileForm(FormInterface $form, $user): void
    {
        $nom = trim((string) $form->get('nom')->getData());
        if ($nom === '') {
            $form->get('nom')->addError(new FormError('Le nom est obligatoire.'));
        } else {
            $nomLength = strlen($nom);
            if ($nomLength < 2 || $nomLength > 100) {
                $form->get('nom')->addError(new FormError('Le nom doit contenir entre 2 et 100 caracteres.'));
            }
            if (!preg_match('/^[\p{L}\p{M}\s\'-]+$/u', $nom)) {
                $form->get('nom')->addError(new FormError('Le nom contient des caracteres invalides.'));
            }
        }

        if ($form->has('pseudo')) {
            $pseudo = trim((string) $form->get('pseudo')->getData());
            if ($pseudo !== '') {
                $pseudoLength = strlen($pseudo);
                if ($pseudoLength < 3 || $pseudoLength > 30) {
                    $form->get('pseudo')->addError(new FormError('Le pseudo doit contenir entre 3 et 30 caracteres.'));
                }
                if (!preg_match('/^[A-Za-z0-9_.-]+$/', $pseudo)) {
                    $form->get('pseudo')->addError(new FormError('Le pseudo contient des caracteres invalides.'));
                }
            }
        }

        $plainPassword = (string) $form->get('plainPassword')->getData();
        if (trim($plainPassword) !== '') {
            if (strlen($plainPassword) < 6) {
                $form->get('plainPassword')->addError(new FormError('Le mot de passe doit contenir au moins 6 caracteres.'));
            }
            if (!preg_match('/[A-Za-z]/', $plainPassword) || !preg_match('/\d/', $plainPassword)) {
                $form->get('plainPassword')->addError(new FormError('Le mot de passe doit contenir au moins une lettre et un chiffre.'));
            }
        }

        if ($nom !== '') {
            $user->setNom($nom);
        }
        if ($form->has('pseudo') && $pseudo !== '') {
            $user->setPseudo($pseudo);
        }
    }

    #[Route('/profile/delete', name: 'app_profile_delete', methods: ['POST'])]
    public function deleteProfile(
        Request $request,
        EntityManagerInterface $entityManager,
        TokenStorageInterface $tokenStorage,
        ManagerRegistry $registry
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->redirectToRoute('app_login');
        }

        // Verify CSRF token
        if (!$this->isCsrfTokenValid('delete_profile', $request->getPayload()->getString('_token'))) {
            $this->addFlash('error', 'Jeton de securite invalide. Veuillez reessayer.');
            return $this->redirectToRoute('app_profile');
        }

        $userId = $user->getId();

        // Clear the security token to prevent refresh attempts
        $tokenStorage->setToken(null);

        // Invalidate the session
        $request->getSession()->invalidate();

        if ($userId === null) {
            return $this->redirectToRoute('app_login');
        }

        try {
            $this->removeUserWithRetry($entityManager, $registry, $userId);
        } catch (LockWaitTimeoutException | EntityManagerClosed $exception) {
            $this->addFlash('error', 'Suppression impossible pour le moment. Reessayez dans quelques secondes.');
        }

        return $this->redirectToRoute('app_login');
    }

    private function removeUserWithRetry(EntityManagerInterface $entityManager, ManagerRegistry $registry, int $userId): void
    {
        $attempts = 0;
        $maxAttempts = 2;
        $manager = $entityManager;

        while ($attempts < $maxAttempts) {
            try {
                if (method_exists($manager, 'isOpen') && !$manager->isOpen()) {
                    $manager = $registry->resetManager();
                }

                $user = $manager->find(User::class, $userId);
                if ($user !== null) {
                    $manager->remove($user);
                    $manager->flush();
                }
                return;
            } catch (EntityManagerClosed $exception) {
                $manager = $registry->resetManager();
                $attempts++;
                if ($attempts >= $maxAttempts) {
                    throw $exception;
                }
            } catch (LockWaitTimeoutException $exception) {
                if (method_exists($manager, 'isOpen') && $manager->isOpen()) {
                    $manager->clear();
                }
                $attempts++;
                if ($attempts >= $maxAttempts) {
                    throw $exception;
                }
                usleep(250000);
            }
        }
    }
}
