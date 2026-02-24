<?php

namespace App\Controller\Api;

use App\Service\RawgApiService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class RawgController extends AbstractController
{
    #[Route('/rawg/games', name: 'app_rawg_browser', methods: ['GET'])]
    public function browser(Request $request): Response
    {
        return $this->render('rawg/browser.html.twig', [
            'initialQuery' => trim((string) $request->query->get('q', '')),
        ]);
    }

    #[Route('/api/rawg/games', name: 'app_api_rawg_games', methods: ['GET'])]
    public function games(Request $request, RawgApiService $rawgApiService): JsonResponse
    {
        if (!$rawgApiService->isConfigured()) {
            return $this->json([
                'error' => 'RAWG_API_KEY is not configured.',
            ], 503);
        }

        $query = trim((string) $request->query->get('q', ''));
        $pageSize = (int) $request->query->get('page_size', 12);
        $games = $rawgApiService->searchGames($query, $pageSize);

        return $this->json([
            'query' => $query,
            'count' => count($games),
            'games' => $games,
        ]);
    }
}
