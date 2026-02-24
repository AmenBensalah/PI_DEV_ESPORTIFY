<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class RawgApiService
{
    private const API_URL = 'https://api.rawg.io/api/games';

    public function __construct(
        private HttpClientInterface $httpClient,
        private string $apiKey,
        private LoggerInterface $logger
    ) {
    }

    public function isConfigured(): bool
    {
        return trim($this->apiKey) !== '';
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function searchGames(string $query = '', int $pageSize = 12): array
    {
        if (!$this->isConfigured()) {
            return [];
        }

        $limit = max(1, min($pageSize, 40));
        $search = trim($query);
        $queryParams = [
            'key' => $this->apiKey,
            'page_size' => $limit,
        ];

        if ($search !== '') {
            $queryParams['search'] = $search;
        } else {
            $queryParams['ordering'] = '-rating';
        }

        try {
            $response = $this->httpClient->request('GET', self::API_URL, [
                'query' => $queryParams,
                'timeout' => 10,
            ]);

            if ($response->getStatusCode() !== 200) {
                return [];
            }

            $payload = $response->toArray(false);
            if (!isset($payload['results']) || !is_array($payload['results'])) {
                return [];
            }

            $games = [];
            foreach ($payload['results'] as $item) {
                if (!is_array($item) || !isset($item['name']) || !is_string($item['name'])) {
                    continue;
                }

                $slug = isset($item['slug']) && is_string($item['slug']) ? $item['slug'] : '';
                $games[] = [
                    'id' => $item['id'] ?? null,
                    'name' => $item['name'],
                    'slug' => $slug,
                    'released' => isset($item['released']) && is_string($item['released']) ? $item['released'] : null,
                    'rating' => isset($item['rating']) && is_numeric($item['rating']) ? (float) $item['rating'] : null,
                    'backgroundImage' => isset($item['background_image']) && is_string($item['background_image']) ? $item['background_image'] : null,
                    'rawgUrl' => $slug !== '' ? 'https://rawg.io/games/' . $slug : null,
                ];
            }

            return $games;
        } catch (\Throwable $exception) {
            $this->logger->error('RAWG API request failed.', [
                'exception' => $exception,
            ]);

            return [];
        }
    }
}
