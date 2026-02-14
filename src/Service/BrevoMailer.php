<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class BrevoMailer
{
    private const API_URL = 'https://api.brevo.com/v3/smtp/email';

    public function __construct(
        private HttpClientInterface $httpClient,
        private string $apiKey,
        private string $senderEmail,
        private string $senderName,
        private LoggerInterface $logger
    ) {
    }

    public function sendPasswordResetCode(string $toEmail, string $code): bool
    {
        if (!$this->isConfigured()) {
            $this->logger->warning('Brevo mailer is not configured; skipping password reset email.');
            return false;
        }

        $payload = [
            'sender' => [
                'name' => $this->senderName !== '' ? $this->senderName : 'Esportify',
                'email' => $this->senderEmail,
            ],
            'to' => [
                ['email' => $toEmail],
            ],
            'subject' => 'Votre code de réinitialisation',
            'textContent' => sprintf(
                "Voici votre code de réinitialisation : %s\n\nCe code expire dans 10 minutes.",
                $code
            ),
        ];

        try {
            $response = $this->httpClient->request('POST', self::API_URL, [
                'headers' => [
                    'accept' => 'application/json',
                    'api-key' => $this->apiKey,
                ],
                'json' => $payload,
                'timeout' => 10,
            ]);

            $status = $response->getStatusCode();
            if ($status >= 200 && $status < 300) {
                return true;
            }

            $this->logger->error('Brevo email send failed.', [
                'status' => $status,
                'body' => $response->getContent(false),
            ]);
        } catch (\Throwable $exception) {
            $this->logger->error('Brevo email send exception.', [
                'exception' => $exception,
            ]);
        }

        return false;
    }

    public function sendTeamSuspensionEmail(string $toEmail, string $teamName, ?string $reason, int $totalTeams): bool
    {
        if (!$this->isConfigured()) {
            $this->logger->warning('Brevo mailer is not configured; skipping team suspension email.');
            return false;
        }

        $reasonText = $reason ?: "équipe suspendue par l'admin";
        
        $payload = [
            'sender' => [
                'name' => $this->senderName !== '' ? $this->senderName : 'Esportify',
                'email' => $this->senderEmail,
            ],
            'to' => [
                ['email' => $toEmail],
            ],
            'subject' => "Suspension de l'activité de votre équipe : " . $teamName,
            'textContent' => sprintf(
                "Bonjour,\n\nNous vous informons que l'activité de votre équipe '%s' a été suspendue sur Esportify.\n\nRaison : %s\n\nIl y a actuellement %d équipes inscrites sur notre plateforme.\n\nCordialement,\nL'équipe Esportify.",
                $teamName,
                $reasonText,
                $totalTeams
            ),
        ];

        try {
            $response = $this->httpClient->request('POST', self::API_URL, [
                'headers' => [
                    'accept' => 'application/json',
                    'api-key' => $this->apiKey,
                ],
                'json' => $payload,
                'timeout' => 10,
            ]);

            $status = $response->getStatusCode();
            return $status >= 200 && $status < 300;
        } catch (\Throwable $exception) {
            $this->logger->error('Brevo email send exception (suspension).', ['exception' => $exception]);
        }

        return false;
    }

    private function isConfigured(): bool
    {
        return trim($this->apiKey) !== '' && trim($this->senderEmail) !== '';
    }
}
