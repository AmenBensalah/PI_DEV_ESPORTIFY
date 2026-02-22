<?php

namespace App\Controller;

use App\Repository\EquipeRepository;
use App\Service\OpenAIChatService;
use App\Service\TeamMatchService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/equipe')]
class TeamMatchController extends AbstractController
{
    #[Route('/match', name: 'app_equipes_match', methods: ['GET', 'POST'], priority: 10)]
    public function match(
        Request $request,
        EquipeRepository $equipeRepository,
        TeamMatchService $teamMatchService,
        OpenAIChatService $openAIChatService
    ): Response {
        $prefs = [
            'niveau' => '',
            'region' => '',
            'game' => '',
            'play_style' => '',
            'goals' => '',
        ];

        $ranked = [];
        $aiExplanations = [];
        $localExplanations = [];
        $aiRaw = null;

        if ($request->isMethod('POST')) {
            $prefs = [
                'niveau' => trim((string) $request->request->get('niveau')),
                'region' => trim((string) $request->request->get('region')),
                'game' => trim((string) $request->request->get('game')),
                'play_style' => trim((string) $request->request->get('play_style')),
                'goals' => trim((string) $request->request->get('goals')),
            ];

            $teams = $equipeRepository->findBy([], ['id' => 'DESC']);
            $ranked = $teamMatchService->rankTeams($prefs, $teams);
            foreach ($ranked as $item) {
                $teamId = $item['team']->getId();
                $percent = (int) ($item['compatibility'] ?? 0);
                $reasons = $item['reasons'] ?? [];
                $label = $percent >= 75 ? 'Très bonne compatibilité' : ($percent >= 50 ? 'Compatibilité moyenne' : 'Compatibilité faible');
                $extra = $reasons ? ('Principaux points: ' . implode(', ', $reasons) . '.') : 'Aucun critère fort détecté.';
                $localExplanations[$teamId] = $label . '. ' . $extra;
            }

            $top = array_slice($ranked, 0, 3);
            if ($openAIChatService->isEnabled() && $top) {
                $payloadTeams = array_map(static function ($item) {
                    $team = $item['team'];
                    return [
                        'id' => $team->getId(),
                        'nom' => $team->getNomEquipe(),
                        'region' => $team->getRegion(),
                        'classement' => $team->getClassement(),
                        'tag' => $team->getTag(),
                        'score' => $item['score'],
                        'reasons' => $item['reasons'],
                    ];
                }, $top);

                $system = 'Tu es un assistant de matching esport. Réponds en JSON uniquement: { "teamId": "explication courte" }. 1 à 2 phrases par équipe. Langue: français.';
                $user = "Préférences joueur:\n".json_encode($prefs, JSON_UNESCAPED_UNICODE)."\n\nÉquipes candidates:\n".json_encode($payloadTeams, JSON_UNESCAPED_UNICODE);

                $aiRaw = $openAIChatService->createCompletion([
                    ['role' => 'system', 'content' => $system],
                    ['role' => 'user', 'content' => $user],
                ], 0.3, 250);

                if (is_string($aiRaw)) {
                    $decoded = json_decode($aiRaw, true);
                    if (is_array($decoded)) {
                        foreach ($decoded as $teamId => $text) {
                            if (is_string($text)) {
                                $aiExplanations[(int) $teamId] = $text;
                            }
                        }
                    }
                }
            }
        }

        return $this->render('equipes/match.html.twig', [
            'prefs' => $prefs,
            'ranked' => $ranked,
            'aiExplanations' => $aiExplanations,
            'localExplanations' => $localExplanations,
            'aiRaw' => $aiRaw,
            'aiEnabled' => $openAIChatService->isEnabled(),
        ]);
    }
}
