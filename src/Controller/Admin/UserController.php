<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Form\User1Type;
use App\Repository\ParticipationRequestRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/users')]
final class UserController extends AbstractController
{
    /*
    #[Route(name: 'app_admin_user_index', methods: ['GET'])]
    public function index(UserRepository $userRepository): Response
    {
        return $this->render('admin/user/index.html.twig', [
            'users' => $userRepository->findAll(),
        ]);
    }
    */

    #[Route('/new', name: 'app_admin_user_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): Response
    {
        $user = new User();
        $form = $this->createForm(User1Type::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $this->validateUserForm($form, $user, true);

            if ($form->isValid()) {
                // Hash the password from the plainPassword field
                /** @var string $plainPassword */
                $plainPassword = $form->get('plainPassword')->getData();

                $hashedPassword = $passwordHasher->hashPassword($user, $plainPassword);
                $user->setPassword($hashedPassword);

                $entityManager->persist($user);
                $entityManager->flush();

                return $this->redirectToRoute('admin_dashboard', [], Response::HTTP_SEE_OTHER);
            }
        }

        return $this->render('admin/user/new.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_admin_user_show', methods: ['GET'])]
    public function show(User $user): Response
    {
        return $this->render('admin/user/show.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_admin_user_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, User $user, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): Response
    {
        $form = $this->createForm(User1Type::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $this->validateUserForm($form, $user, false);

            if ($form->isValid()) {
                // Hash the password from the plainPassword field
                /** @var string $plainPassword */
                $plainPassword = $form->get('plainPassword')->getData();
                if ($plainPassword) {
                    $hashedPassword = $passwordHasher->hashPassword($user, $plainPassword);
                    $user->setPassword($hashedPassword);
                }

                $entityManager->flush();

                return $this->redirectToRoute('admin_dashboard', [], Response::HTTP_SEE_OTHER);
            }
        }

        return $this->render('admin/user/edit.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_admin_user_delete', methods: ['POST'])]
    public function delete(Request $request, User $user, EntityManagerInterface $entityManager, ParticipationRequestRepository $participationRequestRepository): Response
    {
        if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->getPayload()->getString('_token'))) {
            $requests = $participationRequestRepository->findBy(['user' => $user]);
            foreach ($requests as $req) {
                $entityManager->remove($req);
            }
            $entityManager->remove($user);
            $entityManager->flush();
        }

        return $this->redirectToRoute('admin_dashboard', [], Response::HTTP_SEE_OTHER);
    }

    private function validateUserForm(FormInterface $form, User $user, bool $requirePassword): void
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
        if ($requirePassword && trim($plainPassword) === '') {
            $form->get('plainPassword')->addError(new FormError('Le mot de passe est obligatoire.'));
        } elseif (trim($plainPassword) !== '') {
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
