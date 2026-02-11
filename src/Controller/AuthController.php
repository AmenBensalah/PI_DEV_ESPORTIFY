<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\PasswordResetCode;
use App\Repository\PasswordResetCodeRepository;
use App\Repository\UserRepository;
use App\Service\BrevoMailer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class AuthController extends AbstractController
{
    #[Route('/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('tournoi_index');
        }

        $error = $authenticationUtils->getLastAuthenticationError();
        $lastEmail = $authenticationUtils->getLastUsername();

        return $this->render('auth/login.html.twig', [
            'last_email' => $lastEmail,
            'error' => $error,
        ]);
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): Response
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    #[Route('/register', name: 'app_register', methods: ['GET', 'POST'])]
    public function register(Request $request, UserPasswordHasherInterface $hasher, EntityManagerInterface $em): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('tournoi_index');
        }

        if ($request->isMethod('POST')) {
            $data = $request->request->all();

            $user = new User();
            $user->setEmail($data['email'] ?? '');
            $user->setUsername($data['username'] ?? '');
            
            $plainPassword = $data['password'] ?? '';
            $hashedPassword = $hasher->hashPassword($user, $plainPassword);
            $user->setPassword($hashedPassword);
            $user->setRoles(['ROLE_USER']);

            $em->persist($user);
            $em->flush();

            $this->addFlash('success', 'Registration successful! You can now login.');
            return $this->redirectToRoute('app_login');
        }

        return $this->render('auth/register.html.twig');
    }

    #[Route('/password/forgot', name: 'app_forgot_password', methods: ['GET', 'POST'])]
    public function forgotPassword(
        Request $request,
        UserRepository $userRepository,
        PasswordResetCodeRepository $passwordResetCodeRepository,
        EntityManagerInterface $em,
        BrevoMailer $brevoMailer
    ): Response {
        if ($this->getUser()) {
            return $this->redirectToRoute('tournoi_index');
        }

        if ($request->isMethod('POST')) {
            $email = trim((string) $request->request->get('email'));

            if ($email !== '') {
                $user = $userRepository->findOneBy(['email' => $email]);
                if ($user) {
                    $existingCodes = $passwordResetCodeRepository->findBy(['email' => $email]);
                    foreach ($existingCodes as $existingCode) {
                        $em->remove($existingCode);
                    }

                    $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
                    $reset = new PasswordResetCode();
                    $reset->setEmail($email);
                    $reset->setCodeHash(password_hash($code, PASSWORD_DEFAULT));
                    $reset->setCreatedAt(new \DateTimeImmutable());
                    $reset->setExpiresAt((new \DateTimeImmutable())->modify('+10 minutes'));

                    $em->persist($reset);
                    $em->flush();
                    $brevoMailer->sendPasswordResetCode($email, $code);
                }
            }

            $this->addFlash('success', 'Si un compte existe, un code de réinitialisation a été généré.');
            return $this->redirectToRoute('app_reset_password', ['email' => $email]);
        }

        return $this->render('auth/forgot_password.html.twig');
    }

    #[Route('/password/reset', name: 'app_reset_password', methods: ['GET', 'POST'])]
    public function resetPassword(
        Request $request,
        UserRepository $userRepository,
        PasswordResetCodeRepository $passwordResetCodeRepository,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $em
    ): Response {
        if ($this->getUser()) {
            return $this->redirectToRoute('tournoi_index');
        }

        $prefillEmail = (string) $request->query->get('email', '');

        if ($request->isMethod('POST')) {
            $email = trim((string) $request->request->get('email'));
            $code = trim((string) $request->request->get('code'));
            $newPassword = (string) $request->request->get('password');

            $reset = $passwordResetCodeRepository->findOneBy(['email' => $email], ['id' => 'DESC']);
            if (!$reset) {
                $this->addFlash('error', 'Code invalide ou expiré.');
                return $this->redirectToRoute('app_reset_password', ['email' => $email]);
            }

            if ($reset->getExpiresAt() < new \DateTimeImmutable()) {
                $em->remove($reset);
                $em->flush();
                $this->addFlash('error', 'Code expiré. Veuillez recommencer.');
                return $this->redirectToRoute('app_forgot_password');
            }

            if (!password_verify($code, $reset->getCodeHash())) {
                $this->addFlash('error', 'Code invalide ou expiré.');
                return $this->redirectToRoute('app_reset_password', ['email' => $email]);
            }

            $user = $userRepository->findOneBy(['email' => $email]);
            if (!$user) {
                $this->addFlash('error', 'Compte introuvable.');
                return $this->redirectToRoute('app_forgot_password');
            }

            if (strlen($newPassword) < 6) {
                $this->addFlash('error', 'Le mot de passe doit contenir au moins 6 caractères.');
                return $this->redirectToRoute('app_reset_password', ['email' => $email]);
            }

            $user->setPassword($passwordHasher->hashPassword($user, $newPassword));
            $em->remove($reset);
            $em->flush();

            $this->addFlash('success', 'Mot de passe mis à jour. Vous pouvez vous connecter.');
            return $this->redirectToRoute('app_login');
        }

        return $this->render('auth/reset_password.html.twig', [
            'email' => $prefillEmail,
        ]);
    }
}
