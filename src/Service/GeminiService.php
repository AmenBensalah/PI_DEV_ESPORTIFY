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
        // On essaye la dernière génération (2.0) puis les versions flash stables
        $models = [
            'gemini-2.0-flash-exp', // Le "nouveau" très puissant
            'gemini-1.5-flash',
            'gemini-1.5-flash-8b'
        ];

        $lastError = "";

        foreach ($models as $modelName) {
            try {
                $url = "https://generativelanguage.googleapis.com/v1beta/models/" . $modelName . ":generateContent?key=" . $this->apiKey;

                $systemInstruction = "Tu es Nexus_AI, l'assistant intelligent d'E-Sportify. Ton ton est moderne, tech et cyberpunk.\n" .
                                    "Réponds à " . $userName . " de manière réelle, fluide et conversationnelle.\n" .
                                    "Voici le catalogue actuel et le contexte du site pour t'aider :\n" . $context;

                $contents = [];
                
                // Historique
                foreach ($history as $msg) {
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
                        'systemInstruction' => [
                            'parts' => [['text' => $systemInstruction]]
                        ],
                        'generationConfig' => [
                            'temperature' => 0.9,
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

                if ($statusCode === 429 || $statusCode === 503) {
                    $lastError = "Quota atteint pour " . $modelName;
                    continue; 
                }

                $errorData = $response->toArray(false);
                $lastError = $errorData['error']['message'] ?? "Erreur " . $statusCode;

            } catch (\Exception $e) {
                $lastError = $e->getMessage();
                continue;
            }
        }

        return "ERREUR_GEMINI : Désolé, Nexus_AI est temporairement hors ligne. (" . $lastError . ")";
    }
}
