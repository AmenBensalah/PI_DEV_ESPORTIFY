<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
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
        return ($this->apiKey !== null && trim($this->apiKey) !== '' && strpos($this->apiKey, 'sk-') === 0)
            || $this->geminiService->isAvailable();
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

                $userMsg = '';
                $history = [];
                $system = '';

                foreach ($messages as $m) {
                    if (($m['role'] ?? '') === 'system') {
                        $system .= ($m['content'] ?? '') . "\n";
                    } elseif (($m['role'] ?? '') === 'user') {
                        if ($userMsg !== '') {
                            $history[] = ['role' => 'user', 'content' => $userMsg];
                        }
                        $userMsg = (string) ($m['content'] ?? '');
                    } else {
                        $history[] = ['role' => 'bot', 'content' => (string) ($m['content'] ?? '')];
                    }
                }

                $result = $this->geminiService->chat($userMsg, $history, $system);
                if (!is_string($result) || trim($result) === '' || $this->isProviderErrorMessage($result)) {
                    return null;
                }

                return trim($result);
            }

            $this->logger->warning('OpenAI API Key is missing and Gemini is unavailable');
            return null;
        }

        try {
            $this->logger->info('OpenAI Request: Sending prompt to model ' . $this->model);
            $response = $this->httpClient->request('POST', self::API_URL, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => $this->model,
                    'messages' => $messages,
                    'temperature' => $temperature,
                    'max_tokens' => $maxTokens,
                ],
                'timeout' => 15,
            ]);

            $statusCode = $response->getStatusCode();
            if ($statusCode !== 200) {
                $errorData = $response->toArray(false);
                $this->logger->error('OpenAI API Error [' . $statusCode . ']: ' . json_encode($errorData));
                return null;
            }

            $data = $response->toArray();
            $content = $data['choices'][0]['message']['content'] ?? null;
            if (!is_string($content)) {
                return null;
            }

            $content = trim($content);
            if ($content === '' || $this->isProviderErrorMessage($content)) {
                return null;
            }

            return $content;
        } catch (\Throwable $e) {
            $this->logger->error('OpenAI Exception: ' . $e->getMessage());
            return null;
        }
    }

    private function isProviderErrorMessage(string $value): bool
    {
        $normalized = strtoupper(trim($value));
        if ($normalized === '') {
            return true;
        }

        return str_starts_with($normalized, 'ERREUR')
            || str_starts_with($normalized, 'ERROR')
            || str_contains($normalized, 'ERREUR_GEMINI')
            || str_contains($normalized, 'NEXUS_AI EST TEMPORAIREMENT HORS LIGNE')
            || str_contains($normalized, 'OPENAI API ERROR');
    }
}
