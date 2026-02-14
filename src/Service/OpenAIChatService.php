<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class OpenAIChatService
{
    private const API_URL = 'https://api.openai.com/v1/chat/completions';

    public function __construct(
        private HttpClientInterface $httpClient,
        private LoggerInterface $logger,
        private GeminiService $geminiService,
        private ?string $apiKey,
        private string $model
    ) {
    }

    public function isEnabled(): bool
    {
        return ($this->apiKey !== null && trim($this->apiKey) !== '' && strpos($this->apiKey, 'sk-') === 0) || $this->geminiService->isAvailable();
    }

    public function isOpenAIEnabled(): bool
    {
        return $this->apiKey !== null && trim($this->apiKey) !== '' && strpos($this->apiKey, 'sk-') === 0;
    }

    /**
     * @param array<int,array<string,string>> $messages
     */
    public function createCompletion(array $messages, float $temperature = 0.2, int $maxTokens = 300): ?string
    {
        if (!$this->isOpenAIEnabled()) {
            if ($this->geminiService->isAvailable()) {
                $this->logger->info('OpenAI disabled, falling back to Gemini');
                
                $userMsg = "";
                $history = [];
                $system = "";

                foreach ($messages as $m) {
                    if ($m['role'] === 'system') {
                        $system .= $m['content'] . "\n";
                    } elseif ($m['role'] === 'user') {
                        if ($userMsg !== "") {
                            $history[] = ['role' => 'user', 'content' => $userMsg];
                        }
                        $userMsg = $m['content'];
                    } else {
                        $history[] = ['role' => 'bot', 'content' => $m['content']];
                    }
                }

                return $this->geminiService->chat($userMsg, $history, $system);
            }

            $this->logger->warning('OpenAI API Key is missing and Gemini is unavailable');
            return null;
        }

        try {
            $this->logger->info('OpenAI Request: Sending prompt to model ' . $this->model);
            $response = $this->httpClient->request('POST', self::API_URL, [
                'headers' => [
                    'Authorization' => 'Bearer '.$this->apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => $this->model,
                    'messages' => $messages,
                    'temperature' => $temperature,
                    'max_tokens' => $maxTokens,
                ],
                'timeout' => 15, // Increase timeout to 15s
            ]);

            $statusCode = $response->getStatusCode();
            if ($statusCode !== 200) {
                $errorData = $response->toArray(false);
                $this->logger->error('OpenAI API Error [' . $statusCode . ']: ' . json_encode($errorData));
                return "Erreur API OpenAI (Status " . $statusCode . ")";
            }

            $data = $response->toArray();
            $content = $data['choices'][0]['message']['content'] ?? null;

            return is_string($content) ? trim($content) : null;
        } catch (\Throwable $e) {
            $this->logger->error('OpenAI Exception: ' . $e->getMessage());
            return "Erreur de connexion à OpenAI : " . $e->getMessage();
        }
    }
}
