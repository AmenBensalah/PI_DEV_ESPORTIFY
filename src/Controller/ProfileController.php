<?php

namespace App\Controller;

use App\Form\UserProfileFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class ProfileController extends AbstractController
{
    #[Route('/profile', name: 'app_profile', methods: ['GET'])]
    public function profile(): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        return $this->render('home/profile.html.twig', [
            'user' => $this->getUser(),
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

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = $form->get('plainPassword')->getData();
            if (is_string($plainPassword) && $plainPassword !== '') {
                $user->setPassword($passwordHasher->hashPassword($user, $plainPassword));
            }

            $entityManager->flush();
            $this->addFlash('success', 'Profil mis a jour avec succes.');

            return $this->redirectToRoute('app_profile');
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
