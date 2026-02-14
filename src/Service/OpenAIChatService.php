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
        private string $apiKey,
        private string $model
    ) {
    }

    public function isEnabled(): bool
    {
        return trim($this->apiKey) !== '';
    }

    /**
     * @param array<int,array<string,string>> $messages
     */
    public function createCompletion(array $messages, float $temperature = 0.2, int $maxTokens = 300): ?string
    {
        if (!$this->isEnabled()) {
            $this->logger->warning('OpenAI API Key is missing');
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
