<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserProfileFormType;
use App\Repository\CandidatureRepository;
use App\Repository\EquipeRepository;
use App\Repository\PostRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Form\FormError;

class ProfileController extends AbstractController
{
    #[Route('/profile', name: 'app_profile', methods: ['GET'])]
    public function profile(
        PostRepository $postRepository,
        CandidatureRepository $candidatureRepository,
        EquipeRepository $equipeRepository
    ): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException('Utilisateur non authentifie.');
        }

        $posts = $postRepository->findRecentByAuthorWithMedias($user, 20);

        $membership = $candidatureRepository->findAcceptedMembershipByUser($user);
        $team = $membership?->getEquipe();
        $teamRole = null;

        if ($team) {
            $teamRole = 'member';
        } else {
            $managedTeam = $equipeRepository->findOneBy(['manager' => $user]);
            if ($managedTeam) {
                $team = $managedTeam;
                $teamRole = 'manager';
            }
        }

        return $this->render('home/profile.html.twig', [
            'user' => $user,
            'team' => $team,
            'teamRole' => $teamRole,
            'posts' => $posts,
        ]);
    }

    #[Route('/profile/edit', name: 'app_profile_edit', methods: ['GET', 'POST'])]
    public function editProfile(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        $user = $this->getUser();
        $form = $this->createForm(UserProfileFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $nom = trim((string) $form->get('nom')->getData());
            $pseudo = trim((string) $form->get('pseudo')->getData());
            $plainPassword = (string) $form->get('plainPassword')->getData();

            if ($nom === '') {
                $form->get('nom')->addError(new FormError('Le nom est obligatoire.'));
            } elseif (mb_strlen($nom) < 2 || mb_strlen($nom) > 100) {
                $form->get('nom')->addError(new FormError('Le nom doit contenir entre 2 et 100 caractères.'));
            }

            if ($pseudo !== '' && (mb_strlen($pseudo) < 3 || mb_strlen($pseudo) > 30)) {
                $form->get('pseudo')->addError(new FormError('Le pseudo doit contenir entre 3 et 30 caractères.'));
            }

            if ($plainPassword !== '') {
                if (mb_strlen($plainPassword) < 6) {
                    $form->get('plainPassword')->addError(new FormError('Le mot de passe doit contenir au moins 6 caractères.'));
                }
                if (!preg_match('/[A-Za-z]/', $plainPassword) || !preg_match('/\d/', $plainPassword)) {
                    $form->get('plainPassword')->addError(new FormError('Le mot de passe doit contenir au moins une lettre et un chiffre.'));
                }
            }

            if ($form->isValid()) {
            $plainPassword = $form->get('plainPassword')->getData();
            if (is_string($plainPassword) && $plainPassword !== '') {
                $user->setPassword($passwordHasher->hashPassword($user, $plainPassword));
            }

            /** @var UploadedFile|null $avatarFile */
            $avatarFile = $form->get('avatarFile')->getData();
            if ($avatarFile) {
                $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/avatars';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }

                $extension = $avatarFile->guessExtension() ?: $avatarFile->getClientOriginalExtension();
                $filename = bin2hex(random_bytes(12)) . ($extension ? '.' . $extension : '');

                try {
                    $avatarFile->move($uploadDir, $filename);

                    if ($user->getAvatar()) {
                        $oldFile = $uploadDir . '/' . $user->getAvatar();
                        if (is_file($oldFile)) {
                            @unlink($oldFile);
                        }
                    }

                    $user->setAvatar($filename);
                } catch (FileException $e) {
                    $form->get('avatarFile')->addError(new FormError("Impossible d'uploader la photo de profil."));
                    return $this->render('home/edit_profile.html.twig', [
                        'form' => $form->createView(),
                    ]);
                }
            }

            $entityManager->flush();
            $this->addFlash('success', 'Profil mis a jour avec succes.');

            return $this->redirectToRoute('app_profile');
            }
        }

        return $this->render('home/edit_profile.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/profile/delete', name: 'app_profile_delete', methods: ['POST'])]
    public function deleteProfile(Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        if (!$this->isCsrfTokenValid('delete_profile', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('app_profile');
        }

        $user = $this->getUser();
        if ($user) {
            $entityManager->remove($user);
            $entityManager->flush();
        }

        $request->getSession()->invalidate();
        return $this->redirectToRoute('app_logout');
    }
}
