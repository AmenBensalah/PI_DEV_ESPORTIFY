<?php

namespace App\Controller\Api;

use App\Entity\User;
use App\Service\UserPerformanceAIService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class UserPerformanceAiController extends AbstractController
{
    #[Route('/api/profile/performance-ai', name: 'app_api_profile_performance_ai', methods: ['GET'])]
    public function profilePerformance(UserPerformanceAIService $userPerformanceAIService): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json([
                'error' => 'Utilisateur non authentifie.',
            ], 401);
        }

        return $this->json($userPerformanceAIService->buildReport($user));
    }
}

