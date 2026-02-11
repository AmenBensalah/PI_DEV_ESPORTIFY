<?php

namespace App\Controller\Admin;

use App\Form\User1Type;
use App\Repository\EquipeRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class DashboardController extends AbstractController
{
    #[Route('/admin', name: 'admin_root')]
    public function root(): Response
    {
        return $this->redirectToRoute('admin_dashboard');
    }

    #[Route('/admin/dashboard', name: 'admin_dashboard')]
    public function index(UserRepository $userRepository, EquipeRepository $equipeRepository, \App\Repository\ManagerRequestRepository $managerRequestRepository): Response
    {
        $users = $userRepository->findAll();
        $userCount = count($users);
        $equipes = $equipeRepository->findBy([], ['id' => 'DESC'], 5);
        $pendingRequests = $managerRequestRepository->count(['status' => 'pending']);

        return $this->render('admin/dashboard/index.html.twig', [
            'userCount' => $userCount,
            'equipeCount' => $equipeRepository->count([]),
            'reportCount' => 0,
            'pendingRequests' => $pendingRequests,
            'latestUsers' => $userRepository->findBy([], ['id' => 'DESC'], 5),
            'latestEquipes' => $equipes,
        ]);
    }

    #[Route('/admin/utilisateurs', name: 'admin_users')]
    public function users(Request $request, UserRepository $userRepository): Response
    {
        $query = $request->query->get('q');
        $role = $request->query->get('role');
        $sort = $request->query->get('sort', 'id');
        $direction = $request->query->get('direction', 'DESC');

        $users = $userRepository->searchAndSort($query, $role, $sort, $direction);
        if ($request->isXmlHttpRequest() || $request->query->getBoolean('ajax')) {
            return $this->render('admin/users/_table.html.twig', [
                'users' => $users,
            ]);
        }

        return $this->render('admin/users/index.html.twig', [
            'users' => $users,
            'currentQuery' => $query,
            'currentRole' => $role,
            'currentSort' => $sort,
            'currentDirection' => $direction
        ]);
    }

    #[Route('/admin/boutique', name: 'admin_boutique')]
    public function boutique(): Response
    {
        return $this->render('admin/boutique/index.html.twig', []);
    }

    #[Route('/admin/profile', name: 'admin_profile', methods: ['GET', 'POST'])]
    public function profile(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager
    ): Response {
        $user = $this->getUser();
        $form = $this->createForm(User1Type::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $this->validateAdminProfileForm($form, $user);

            if ($form->isValid()) {
                $plainPassword = $form->get('plainPassword')->getData();

                if ($plainPassword) {
                    $hashedPassword = $passwordHasher->hashPassword($user, $plainPassword);
                    $user->setPassword($hashedPassword);
                }

                $entityManager->flush();

                $this->addFlash('success', 'Profil mis a jour avec succes!');
                return $this->redirectToRoute('admin_profile');
            }
        }

        return $this->render('admin/profile/profile.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    private function validateAdminProfileForm(FormInterface $form, $user): void
    {
        $email = trim((string) $form->get('email')->getData());
        if ($email === '') {
            $form->get('email')->addError(new FormError("L'email est obligatoire."));
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $form->get('email')->addError(new FormError("Le format de l'email est invalide."));
        }

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

        if ($email !== '') {
            $user->setEmail($email);
        }
        if ($nom !== '') {
            $user->setNom($nom);
        }
        if ($form->has('pseudo') && $pseudo !== '') {
            $user->setPseudo($pseudo);
        }
    }
}
