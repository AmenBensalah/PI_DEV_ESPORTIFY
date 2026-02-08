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

    public function chat(string $userMessage, array $history = [], string $context = '', string $userName = 'Visiteur'): string
    {
        try {
            // Utilisation du modèle gemini-3-flash-preview pour les dernières performances
            $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-3-flash-preview:generateContent?key=" . $this->apiKey;

            $contents = [];
            
            // System instruction via context (Gemini 1.5 doesn't have a direct 'system' role in the contents array easily, 
            // but we can prepend it to the context)
            $systemInstruction = "Tu es Nexus_AI, l'assistant intelligent d'E-Sportify. Ton ton est moderne, tech et cyberpunk. \n" .
                                "Réponds à " . $userName . " de manière réelle, fluide et conversationnelle. \n" .
                                "Voici le contexte actuel du site : \n" . $context;

            // Ajout de l'historique et du contexte
            // On peut mettre le contexte au début du premier message ou comme une introduction
            
            foreach ($history as $msg) {
                $role = ($msg['role'] === 'bot' || $msg['role'] === 'assistant' || $msg['role'] === 'model') ? 'model' : 'user';
                $contents[] = [
                    'role' => $role,
                    'parts' => [
                        ['text' => $msg['content']]
                    ]
                ];
            }

            // If empty contents, start with system instructions
            if (empty($contents)) {
                $contents[] = [
                    'role' => 'user',
                    'parts' => [
                        ['text' => "SYSTEM_INSTRUCTION: " . $systemInstruction . "\n\nMESSAGE UTILISATEUR: " . $userMessage]
                    ]
                ];
            } else {
                // Add system context to the user message if it's the first turn or keep it in mind
                $contents[] = [
                    'role' => 'user',
                    'parts' => [
                        ['text' => $userMessage]
                    ]
                ];
            }

            $response = $this->httpClient->request('POST', $url, [
                'headers' => [
                    'Content-Type' => 'application/json'
                ],
                'json' => [
                    'contents' => $contents,
                    'generationConfig' => [
                        'temperature' => 0.8,
                        'maxOutputTokens' => 500,
                        'topK' => 40,
                        'topP' => 0.95,
                    ]
                ],
                'timeout' => 20
            ]);

            if ($response->getStatusCode() !== 200) {
                $error = $response->toArray(false);
                throw new \Exception($error['error']['message'] ?? "Erreur Gemini API " . $response->getStatusCode());
            }

            $result = $response->toArray();
            
            if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
                return $result['candidates'][0]['content']['parts'][0]['text'];
            }

            return "Désolé, ma matrice de réponse est vide (No content found).";

        } catch (\Exception $e) {
            return "ERREUR_GEMINI : " . $e->getMessage();
        }
    }
}
