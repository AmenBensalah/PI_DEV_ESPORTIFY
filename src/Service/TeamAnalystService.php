<?php

namespace App\Service;

use App\Entity\Equipe;
use App\Entity\User;

class TeamAnalystService
{
    public function __construct(
        private OpenAIChatService $openAIChatService,
        private TeamBalanceService $teamBalanceService,
        private TeamPerformanceService $teamPerformanceService,
        private TeamLevelStatsService $teamLevelStatsService
    ) {
    }

    public function analyzeTeamForUser(Equipe $equipe, ?string $question = null): ?string
    {
        if (!$this->openAIChatService->isEnabled()) {
            return "Désolé, l'analyseur IA est actuellement désactivé.";
        }

        // Gather team metrics
        $balance = $this->teamBalanceService->calculateBalance($equipe);
        $performance = $this->teamPerformanceService->calculatePerformance($equipe);
        $levelStats = $this->teamLevelStatsService->calculateLevelStats($equipe);

        $teamData = [
            'name' => $equipe->getNomEquipe(),
            'stats' => [
                'balance_score' => $balance->balanceScore,
                'role_distribution' => $balance->roleCounts,
                'average_level' => $levelStats->averageScore,
                'is_active' => $levelStats->isActive,
                'recent_recruitment' => [
                    'total_last_30_days' => $performance->totalLast30,
                    'accepted_last_30_days' => $performance->acceptedLast30,
                    'trend' => $performance->trendTotal
                ],
                'strengths' => $levelStats->strengths,
                'weaknesses' => $levelStats->weaknesses
            ]
        ];

        $systemPrompt = "Tu es 'Esportify AI', un analyste expert en esport. Ton rôle est d'aider les joueurs à comprendre les statistiques d'une équipe et de les conseiller.
        Sois professionnel, précis et encourageant. Utilise des emojis pour rendre la conversation vivante.
        Voici les données actuelles de l'équipe {$equipe->getNomEquipe()}: " . json_encode($teamData, JSON_UNESCAPED_UNICODE);

        $messages = [
            ['role' => 'system', 'content' => $systemPrompt]
        ];

        if ($question) {
            $messages[] = ['role' => 'user', 'content' => $question];
        } else {
            $messages[] = ['role' => 'user', 'content' => "Fais-moi un résumé rapide de cette équipe, ses points forts et si c'est un bon moment pour postuler."];
        }

        return $this->openAIChatService->createCompletion($messages, 0.7, 500);
    }
    public function analyzeHubQuestion(array $allEquipes, string $question): ?string
    {
        if (!$this->openAIChatService->isEnabled()) {
            return "Désolé, l'assistant IA est actuellement désactivé.";
        }

        try {
            // Prepare a summary of available teams for context - Limit to newest 10 to avoid token overload
            $latestTeams = array_slice($allEquipes, 0, 10);
            $teamsSummary = array_map(function(Equipe $e) {
                return [
                    'nom' => $e->getNomEquipe(),
                    'rang' => $e->getClassement(),
                    'region' => $e->getRegion() ?: 'World',
                    'membres' => count($e->getMembres()),
                    'desc' => mb_strimwidth($e->getDescription() ?: '', 0, 80, '...')
                ];
            }, $latestTeams);

            $systemPrompt = "Tu es 'Esportify AI', un expert en recrutement et analyse esport. Ton but est de guider les joueurs dans le Hub.
            Voici une liste des dernières équipes : " . json_encode($teamsSummary, JSON_UNESCAPED_UNICODE) . "
            Si l'utilisateur pose une question sur une de ces équipes ou sur la recherche d'équipe en général, réponds précisément.
            Si l'équipe n'est pas dans la liste, demande-lui d'aller directement sur le profil de cette équipe.
            Sois ultra-concis et utilise des emojis. Réponds toujours en FR.";

            $messages = [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $question]
            ];

            return $this->openAIChatService->createCompletion($messages, 0.7, 400);
        } catch (\Exception $e) {
            return "Désolé, mon cerveau de hub a eu un court-circuit. Pose une question plus courte !";
        }
    }
}
