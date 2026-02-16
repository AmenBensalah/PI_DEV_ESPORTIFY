<?php

namespace App\Controller;
use App\Enum\Role;
use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Security\SecurityAuthenticator;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register')]
    public function register(
        Request $request,
        UserPasswordHasherInterface $userPasswordHasher,
        Security $security,
        EntityManagerInterface $entityManager,
        NotificationService $notificationService
    ): Response
    {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $this->validateRegistrationForm($form, $user);

            if ($form->isValid()) {
                /** @var string $plainPassword */
                $plainPassword = $form->get('plainPassword')->getData();

                // encode the plain password
                $user->setPassword($userPasswordHasher->hashPassword($user, $plainPassword));
                $user->setRole(Role::JOUEUR);

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
                        $user->setAvatar($filename);
                    } catch (FileException $e) {
                        $form->get('avatarFile')->addError(new FormError("Impossible d'uploader la photo de profil."));
                    }
                }

                if (!$form->isValid()) {
                    return $this->render('registration/register.html.twig', [
                        'registrationForm' => $form,
                    ]);
                }

                $entityManager->persist($user);
                $entityManager->flush();

                $notificationService->notifyUser(
                    $user,
                    'Bienvenue sur E-Sportify',
                    'Votre compte a été créé avec succès. Commencez à explorer le fil d\'actualité.',
                    $this->generateUrl('fil_home'),
                    'welcome'
                );

                // do anything else you need here, like send an email

                return $security->login($user, SecurityAuthenticator::class, 'main');
            }
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form,
        ]);
    }

    private function validateRegistrationForm(FormInterface $form, User $user): void
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

        $pseudo = trim((string) $form->get('pseudo')->getData());
        if ($pseudo === '') {
            $form->get('pseudo')->addError(new FormError('Le pseudo est obligatoire.'));
        } else {
            $pseudoLength = strlen($pseudo);
            if ($pseudoLength < 3 || $pseudoLength > 30) {
                $form->get('pseudo')->addError(new FormError('Le pseudo doit contenir entre 3 et 30 caracteres.'));
            }
            if (!preg_match('/^[A-Za-z0-9_.-]+$/', $pseudo)) {
                $form->get('pseudo')->addError(new FormError('Le pseudo contient des caracteres invalides.'));
            }
        }

        $plainPassword = (string) $form->get('plainPassword')->getData();
        if (trim($plainPassword) === '') {
            $form->get('plainPassword')->addError(new FormError('Le mot de passe est obligatoire.'));
        } else {
            if (strlen($plainPassword) < 6) {
                $form->get('plainPassword')->addError(new FormError('Le mot de passe doit contenir au moins 6 caracteres.'));
            }
            if (!preg_match('/[A-Za-z]/', $plainPassword) || !preg_match('/\d/', $plainPassword)) {
                $form->get('plainPassword')->addError(new FormError('Le mot de passe doit contenir au moins une lettre et un chiffre.'));
            }
        }

        $confirmPassword = (string) $form->get('confirmPassword')->getData();
        if (trim($confirmPassword) === '') {
            $form->get('confirmPassword')->addError(new FormError('La confirmation du mot de passe est obligatoire.'));
        } elseif ($plainPassword !== '' && $confirmPassword !== $plainPassword) {
            $form->get('confirmPassword')->addError(new FormError('Les mots de passe ne correspondent pas.'));
        }

        if ($email !== '') {
            $user->setEmail($email);
        }
        if ($nom !== '') {
            $user->setNom($nom);
        }
        if ($pseudo !== '') {
            $user->setPseudo($pseudo);
        }

        $faceDescriptorRaw = trim((string) $form->get('faceDescriptor')->getData());
        $faceDescriptor = $this->parseFaceDescriptor($faceDescriptorRaw, $form);
        $user->setFaceDescriptor($faceDescriptor);
    }

    /**
     * @return list<float>|null
     */
    private function parseFaceDescriptor(string $rawDescriptor, FormInterface $form): ?array
    {
        if ($rawDescriptor === '') {
            return null;
        }

        try {
            $decoded = json_decode($rawDescriptor, true, flags: JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            $form->get('faceDescriptor')->addError(new FormError('Les donnees Face ID sont invalides.'));

            return null;
        }

        if (!is_array($decoded) || $decoded === []) {
            $form->get('faceDescriptor')->addError(new FormError('Les donnees Face ID sont invalides.'));

            return null;
        }

        if (count($decoded) !== 128) {
            $form->get('faceDescriptor')->addError(new FormError('Le vecteur Face ID doit contenir 128 valeurs.'));

            return null;
        }

        $vector = [];
        foreach ($decoded as $value) {
            if (!is_int($value) && !is_float($value)) {
                $form->get('faceDescriptor')->addError(new FormError('Le vecteur Face ID contient des valeurs invalides.'));

                return null;
            }

            $floatValue = (float) $value;
            if (!is_finite($floatValue) || abs($floatValue) > 10.0) {
                $form->get('faceDescriptor')->addError(new FormError('Le vecteur Face ID contient des valeurs invalides.'));

                return null;
            }

            $vector[] = $floatValue;
        }

        return $vector;
    }
}
