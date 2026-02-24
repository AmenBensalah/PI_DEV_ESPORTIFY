<?php

namespace App\Controller;

use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class OAuthController extends AbstractController
{
    #[Route('/connect/google', name: 'connect_google_start')]
    public function connectGoogle(ClientRegistry $clientRegistry): Response
    {
        if (!$this->isProviderConfigured('GOOGLE')) {
            $this->addFlash('error', 'Google login is not configured yet.');
            return $this->redirectToRoute('app_login');
        }

        return $clientRegistry
            ->getClient('google')
            ->redirect(['email', 'profile']);
    }

    #[Route('/connect/google/check', name: 'connect_google_check')]
    public function connectGoogleCheck(): never
    {
        throw new \LogicException('This route is handled by the Google OAuth authenticator.');
    }

    #[Route('/connect/discord', name: 'connect_discord_start')]
    public function connectDiscord(ClientRegistry $clientRegistry): Response
    {
        if (!$this->isProviderConfigured('DISCORD')) {
            $this->addFlash('error', 'Discord login is not configured yet.');
            return $this->redirectToRoute('app_login');
        }

        return $clientRegistry
            ->getClient('discord')
            ->redirect(['identify', 'email']);
    }

    #[Route('/connect/discord/check', name: 'connect_discord_check')]
    public function connectDiscordCheck(): never
    {
        throw new \LogicException('This route is handled by the Discord OAuth authenticator.');
    }

    private function isProviderConfigured(string $provider): bool
    {
        $id = $this->readEnvValue("OAUTH_{$provider}_CLIENT_ID");
        $secret = $this->readEnvValue("OAUTH_{$provider}_CLIENT_SECRET");

        return $id !== '' && $secret !== '';
    }

    private function readEnvValue(string $name): string
    {
        $value = $_SERVER[$name] ?? $_ENV[$name] ?? getenv($name) ?? '';
        return trim((string) $value);
    }
}
