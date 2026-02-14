<?php

namespace App\Service;

use App\Entity\Candidature;
use App\Entity\Equipe;

class CandidatureAiService
{
    public function __construct(private OpenAIChatService $openAIChatService)
    {
    }

    public function isEnabled(): bool
    {
        return $this->openAIChatService->isEnabled();
    }

    /**
     * @return array{summary:string,recommendation:string,flags:string[]}|null
     */
    public function analyze(Candidature $candidature, Equipe $equipe): ?array
    {
        if (!$this->isEnabled()) {
            return null;
        }

        $payload = [
            'team' => [
                'name' => $equipe->getNomEquipe(),
                'classement' => $equipe->getClassement(),
                'region' => $equipe->getRegion(),
                'tag' => $equipe->getTag(),
            ],
            'candidate' => [
                'niveau' => $candidature->getNiveau(),
                'motivation' => $candidature->getMotivation(),
                'reason' => $candidature->getReason(),
                'play_style' => $candidature->getPlayStyle(),
            ],
        ];

        $system = 'Tu es un assistant de recrutement esport. Réponds en JSON strict: '
            . '{"summary":"...","recommendation":"accepter|attendre|refuser","flags":["...","..."]}. '
            . 'Résumé: 1 phrase courte en français.';

        $user = "Analyse la candidature:\n" . json_encode($payload, JSON_UNESCAPED_UNICODE);

        $raw = $this->openAIChatService->createCompletion([
            ['role' => 'system', 'content' => $system],
            ['role' => 'user', 'content' => $user],
        ], 0.2, 220);

        if (!is_string($raw) || $raw === '') {
            return null;
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return null;
        }

        $summary = is_string($decoded['summary'] ?? null) ? $decoded['summary'] : null;
        $rec = is_string($decoded['recommendation'] ?? null) ? $decoded['recommendation'] : null;
        $flags = $decoded['flags'] ?? [];

        if (!$summary || !$rec || !is_array($flags)) {
            return null;
        }

        $flagsClean = [];
        foreach ($flags as $flag) {
            if (is_string($flag) && $flag !== '') {
                $flagsClean[] = $flag;
            }
        }

        return [
            'summary' => $summary,
            'recommendation' => $rec,
            'flags' => $flagsClean,
        ];
    }
}
