<?php

namespace App\Controller;

use App\Repository\EquipeRepository;
use App\Service\TeamAnalystService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

use Psr\Log\LoggerInterface;

#[Route('/api/team-bot')]
class TeamAiBotController extends AbstractController
{
    public function __construct(private LoggerInterface $logger) {}
    #[Route('/{id}/ask', name: 'app_api_team_bot_ask', methods: ['POST'])]
    public function ask(
        string $id,
        Request $request,
        EquipeRepository $equipeRepository,
        TeamAnalystService $aiService
    ): JsonResponse {
        try {
            $data = json_decode($request->getContent(), true);
            $question = $data['question'] ?? null;

            if (!$question) {
                return new JsonResponse(['error' => 'Veuillez poser une question'], 400);
            }

            if ($id === 'hub') {
                $allTeams = $equipeRepository->findAll();
                $answer = $aiService->analyzeHubQuestion($allTeams, $question);
                
                return new JsonResponse([
                    'answer' => $answer ?: "Je suis là pour vous aider à explorer le Hub ! Je n'ai pas pu analyser votre question, précisez votre demande. 🎮"
                ]);
            }

            $equipe = $equipeRepository->find($id);
            
            if (!$equipe) {
                return new JsonResponse(['error' => 'Équipe non trouvée'], 404);
            }

            // Get AI response
            $answer = $aiService->analyzeTeamForUser($equipe, $question);

            return new JsonResponse([
                'answer' => $answer ?: "Je n'ai pas pu générer de réponse. Réessayez."
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('TeamAiBot error: ' . $e->getMessage());
            return new JsonResponse(['error' => 'Server Error: ' . $e->getMessage()], 500);
        }
    }
}
