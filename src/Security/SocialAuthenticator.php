<?php

namespace App\Security;

use App\Entity\User;
use App\Enum\Role;
use App\Repository\CandidatureRepository;
use App\Repository\EquipeRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Security\Authenticator\OAuth2Authenticator;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class SocialAuthenticator extends OAuth2Authenticator
{
    public function __construct(
        private readonly ClientRegistry $clientRegistry,
        private readonly UserRepository $userRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly EquipeRepository $equipeRepository,
        private readonly CandidatureRepository $candidatureRepository,
    ) {
    }

    public function supports(Request $request): ?bool
    {
        return in_array((string) $request->attributes->get('_route'), [
            'connect_google_check',
            'connect_discord_check',
        ], true);
    }

    public function authenticate(Request $request): Passport
    {
        $providerKey = $this->providerFromRoute((string) $request->attributes->get('_route'));
        $client = $this->clientRegistry->getClient($providerKey);
        $accessToken = $this->fetchAccessToken($client);

        return new SelfValidatingPassport(
            new UserBadge(
                $providerKey . ':' . $accessToken->getToken(),
                function () use ($providerKey, $client, $accessToken): User {
                    $oauthUser = $client->fetchUserFromToken($accessToken);
                    $email = $this->extractEmail($oauthUser);

                    if ($email === null || $email === '') {
                        throw new CustomUserMessageAuthenticationException(sprintf(
                            '%s login failed: no email returned by provider.',
                            ucfirst($providerKey)
                        ));
                    }

                    $email = strtolower(trim($email));
                    $existingUser = $this->userRepository->findOneBy(['email' => $email]);

                    if ($existingUser instanceof User) {
                        return $existingUser;
                    }

                    $displayName = $this->extractDisplayName($oauthUser, $providerKey, $email);
                    $user = (new User())
                        ->setEmail($email)
                        ->setNom($displayName)
                        ->setPseudo($displayName)
                        ->setRole(Role::JOUEUR);

                    $randomPassword = bin2hex(random_bytes(32));
                    $user->setPassword($this->passwordHasher->hashPassword($user, $randomPassword));

                    $this->entityManager->persist($user);
                    $this->entityManager->flush();

                    return $user;
                }
            )
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        $user = $token->getUser();
        if ($user instanceof User && $request->hasSession()) {
            $session = $request->getSession();

            $membership = $this->candidatureRepository
                ->createQueryBuilder('c')
                ->andWhere('c.user = :user')
                ->andWhere('c.statut LIKE :accepted')
                ->setParameter('user', $user)
                ->setParameter('accepted', 'Accept%')
                ->setMaxResults(1)
                ->getQuery()
                ->getOneOrNullResult();

            if ($membership) {
                $session->set('my_team_id', $membership->getEquipe()->getId());
            } else {
                $managedTeam = $this->equipeRepository->findOneBy(['manager' => $user]);
                if ($managedTeam) {
                    $session->set('my_team_id', $managedTeam->getId());
                }
            }
        }

        if (in_array('ROLE_ADMIN', $token->getRoleNames(), true)) {
            return new RedirectResponse($this->urlGenerator->generate('admin_dashboard'));
        }

        return new RedirectResponse($this->urlGenerator->generate('fil_home'));
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        if ($request->hasSession()) {
            $message = strtr($exception->getMessageKey(), $exception->getMessageData());
            $request->getSession()->getFlashBag()->add('error', $message);
        }

        return new RedirectResponse($this->urlGenerator->generate('app_login'));
    }

    private function providerFromRoute(string $route): string
    {
        return match ($route) {
            'connect_google_check' => 'google',
            'connect_discord_check' => 'discord',
            default => throw new CustomUserMessageAuthenticationException('Unsupported social login callback.'),
        };
    }

    private function extractEmail(object $oauthUser): ?string
    {
        if (method_exists($oauthUser, 'getEmail')) {
            $email = $oauthUser->getEmail();
            return is_string($email) ? $email : null;
        }

        if (method_exists($oauthUser, 'toArray')) {
            $payload = $oauthUser->toArray();
            $email = $payload['email'] ?? null;
            return is_string($email) ? $email : null;
        }

        return null;
    }

    private function extractDisplayName(object $oauthUser, string $providerKey, string $fallbackEmail): string
    {
        $candidate = null;

        if ($providerKey === 'google' && method_exists($oauthUser, 'getName')) {
            $candidate = $oauthUser->getName();
        }

        if ($providerKey === 'discord' && method_exists($oauthUser, 'getUsername')) {
            $candidate = $oauthUser->getUsername();
        }

        if (!is_string($candidate) || trim($candidate) === '') {
            $candidate = strstr($fallbackEmail, '@', true) ?: $fallbackEmail;
        }

        return substr(trim($candidate), 0, 100);
    }
}
