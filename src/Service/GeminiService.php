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
        // On force le modèle le plus stable et universel
        $modelName = 'gemini-1.5-flash';
        
        try {
            // Utilisation de l'endpoint stable V1
            $url = "https://generativelanguage.googleapis.com/v1/models/" . $modelName . ":generateContent?key=" . $this->apiKey;

            $systemPrompt = "Ton nom est Nexus_AI, l'assistant intelligent d'E-Sportify. Style moderne et cyberpunk.\n" .
                            "Réponds à " . $userName . " de manière fluide.\n" .
                            "Contexte actuel :\n" . $context;

            $contents = [];
            
            // Injection du système
            $contents[] = [
                'role' => 'user',
                'parts' => [['text' => "INSTRUCTION SYSTÈME: " . $systemPrompt]]
            ];
            $contents[] = [
                'role' => 'model',
                'parts' => [['text' => "SYSTÈME_ACTIF. Bonjour " . $userName . ". Je suis prêt à vous aider."]]
            ];

            // Historique
            foreach ($history as $msg) {
                if (!isset($msg['content']) || empty($msg['content'])) continue;
                $role = ($msg['role'] === 'bot' || $msg['role'] === 'assistant' || $msg['role'] === 'model') ? 'model' : 'user';
                $contents[] = [
                    'role' => $role,
                    'parts' => [['text' => $msg['content']]]
                ];
            }

            // Message actuel
            $contents[] = [
                'role' => 'user',
                'parts' => [['text' => $userMessage]]
            ];

            $response = $this->httpClient->request('POST', $url, [
                'headers' => ['Content-Type' => 'application/json'],
                'json' => [
                    'contents' => $contents,
                    'generationConfig' => [
                        'temperature' => 0.7,
                        'maxOutputTokens' => 1000
                    ]
                ],
                'timeout' => 30
            ]);

            $statusCode = $response->getStatusCode();
            
            if ($statusCode === 200) {
                $result = $response->toArray();
                if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
                    return $result['candidates'][0]['content']['parts'][0]['text'];
                }
            }

            $errorData = $response->toArray(false);
            $err = $errorData['error']['message'] ?? "Code " . $statusCode;
            return "ERREUR_GEMINI : Désolé, Nexus_AI est temporairement hors ligne. (" . $err . ")";

        } catch (\Exception $e) {
            return "ERREUR_GEMINI : Désolé, Nexus_AI est temporairement hors ligne. (" . $e->getMessage() . ")";
        }
    }
}
