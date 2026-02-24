<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class GeminiService
{
    private string $apiKey;
    private HttpClientInterface $httpClient;

    public function __construct(string $geminiApiKey, HttpClientInterface $httpClient)
    {
        $this->apiKey = $geminiApiKey;
        $this->httpClient = $httpClient;
    }

    public function isAvailable(): bool
    {
        return trim($this->apiKey) !== '' && strpos($this->apiKey, 'AIza') === 0;
    }

    public function chat(string $userMessage, array $history = [], string $context = '', string $userName = 'Visiteur'): string
    {
        $modelCandidates = [
            'gemini-2.5-flash',
            'gemini-2.5-flash-lite',
            'gemini-flash-latest',
            'gemini-flash-lite-latest',
            'gemini-2.0-flash',
            'gemini-2.0-flash-001',
            'gemini-2.0-flash-lite',
            'gemini-2.0-flash-lite-001',
        ];
        $apiVersions = ['v1', 'v1beta'];

        try {
            $systemPrompt = "Ton nom est Nexus_AI, l'assistant intelligent d'E-Sportify. Style moderne et cyberpunk.\n"
                . "Reponds a {$userName} de maniere fluide.\n"
                . "Contexte actuel :\n{$context}";

            $contents = [];

            $contents[] = [
                'role' => 'user',
                'parts' => [['text' => "INSTRUCTION SYSTEME: {$systemPrompt}"]],
            ];
            $contents[] = [
                'role' => 'model',
                'parts' => [['text' => "SYSTEME_ACTIF. Bonjour {$userName}. Je suis pret a vous aider."]],
            ];

            foreach ($history as $msg) {
                if (!isset($msg['content']) || empty($msg['content'])) {
                    continue;
                }
                $role = ($msg['role'] === 'bot' || $msg['role'] === 'assistant' || $msg['role'] === 'model')
                    ? 'model'
                    : 'user';
                $contents[] = [
                    'role' => $role,
                    'parts' => [['text' => (string) $msg['content']]],
                ];
            }

            $contents[] = [
                'role' => 'user',
                'parts' => [['text' => $userMessage]],
            ];

            $lastError = null;
            $bestError = null;
            $bestErrorScore = -1;
            foreach ($apiVersions as $version) {
                foreach ($modelCandidates as $modelName) {
                    $url = sprintf(
                        'https://generativelanguage.googleapis.com/%s/models/%s:generateContent?key=%s',
                        $version,
                        $modelName,
                        $this->apiKey
                    );

                    $response = $this->httpClient->request('POST', $url, [
                        'headers' => ['Content-Type' => 'application/json'],
                        'json' => [
                            'contents' => $contents,
                            'generationConfig' => [
                                'temperature' => 0.7,
                                'maxOutputTokens' => 1000,
                            ],
                        ],
                        'timeout' => 30,
                    ]);

                    $statusCode = $response->getStatusCode();
                    if ($statusCode === 200) {
                        $result = $response->toArray();
                        if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
                            return (string) $result['candidates'][0]['content']['parts'][0]['text'];
                        }
                    }

                    $errorData = $response->toArray(false);
                    $errorMessage = (string) ($errorData['error']['message'] ?? ('Code ' . $statusCode));
                    $lastError = $errorMessage;

                    // Keep the most useful error seen across attempts (e.g. 429 over 404).
                    $errorCode = (int) ($errorData['error']['code'] ?? $statusCode);
                    $errorStatus = strtoupper((string) ($errorData['error']['status'] ?? ''));
                    $score = $this->errorPriorityScore($errorCode, $errorStatus);
                    if ($score >= $bestErrorScore) {
                        $bestErrorScore = $score;
                        $bestError = $errorMessage;
                    }
                }
            }

            $err = $bestError ?? $lastError ?? 'Aucun modele Gemini compatible trouve.';
            return 'ERREUR_GEMINI : Desole, Nexus_AI est temporairement hors ligne. (' . $err . ')';
        } catch (\Throwable $e) {
            return 'ERREUR_GEMINI : Desole, Nexus_AI est temporairement hors ligne. (' . $e->getMessage() . ')';
        }
    }

    private function errorPriorityScore(int $errorCode, string $errorStatus): int
    {
        if ($errorCode === 429 || $errorStatus === 'RESOURCE_EXHAUSTED') {
            return 5;
        }
        if ($errorCode >= 500) {
            return 4;
        }
        if ($errorCode === 401 || $errorCode === 403) {
            return 3;
        }
        if ($errorCode === 400) {
            return 2;
        }
        if ($errorCode === 404) {
            return 1;
        }

        return 0;
    }
}
