<?php

namespace App\Service;

class ReasonQualityAiService
{
    public function __construct(private OpenAIChatService $chatService)
    {
    }

    public function isEnabled(): bool
    {
        return $this->chatService->isEnabled();
    }

    /**
     * @return array{score:int,label:string,analysis:string}
     */
    public function analyze(string $reasonText): array
    {
        $text = trim($reasonText);
        if ($text === '') {
            return [
                'score' => 0,
                'label' => 'insuffisant',
                'analysis' => 'Texte vide.',
            ];
        }

        if ($this->isEnabled()) {
            $system = 'Tu es un évaluateur RH e-sport. Réponds en JSON strict: '
                . '{"score":0-100,"label":"pro|moyen|faible","analysis":"..."} '
                . 'Le score doit évaluer professionnalisme, clarté, motivation et cohérence.';
            $user = "Évalue ce texte de candidature:\n" . $text;

            $raw = $this->chatService->createCompletion([
                ['role' => 'system', 'content' => $system],
                ['role' => 'user', 'content' => $user],
            ], 0.1, 160);

            if (is_string($raw) && $raw !== '') {
                $decoded = json_decode($raw, true);
                if (is_array($decoded)) {
                    $score = (int) ($decoded['score'] ?? 0);
                    $label = is_string($decoded['label'] ?? null) ? mb_strtolower((string) $decoded['label']) : 'moyen';
                    $analysis = is_string($decoded['analysis'] ?? null) ? $decoded['analysis'] : 'Analyse générée.';

                    return [
                        'score' => max(0, min(100, $score)),
                        'label' => $this->normalizeLabel($label),
                        'analysis' => $analysis,
                    ];
                }
            }
        }

        return $this->heuristic($text);
    }

    /**
     * @return array{score:int,label:string,analysis:string}
     */
    private function heuristic(string $text): array
    {
        $score = 20;
        $len = mb_strlen($text);

        if ($len >= 180) {
            $score += 28;
        } elseif ($len >= 100) {
            $score += 20;
        } elseif ($len >= 50) {
            $score += 10;
        }

        $proWords = [
            'objectif', 'motivation', 'discipline', 'communication', 'stratégie',
            'progression', 'entraînement', 'esprit d\'équipe', 'compétitif', 'engagement',
        ];
        $penaltyWords = ['svp', 'stp', 'je veux juste', 'add moi', 'plz', 'nn', 'pk'];

        foreach ($proWords as $w) {
            if (str_contains(mb_strtolower($text), $w)) {
                $score += 5;
            }
        }
        foreach ($penaltyWords as $w) {
            if (str_contains(mb_strtolower($text), $w)) {
                $score -= 7;
            }
        }

        $score = max(0, min(100, $score));
        $label = $score >= 75 ? 'pro' : ($score >= 45 ? 'moyen' : 'faible');
        $analysis = $label === 'pro'
            ? 'Réponse structurée et professionnelle.'
            : ($label === 'moyen' ? 'Réponse correcte mais peut être plus détaillée.' : 'Réponse trop courte ou peu professionnelle.');

        return [
            'score' => $score,
            'label' => $label,
            'analysis' => $analysis,
        ];
    }

    private function normalizeLabel(string $label): string
    {
        return match (true) {
            str_contains($label, 'pro') => 'pro',
            str_contains($label, 'faible') => 'faible',
            default => 'moyen',
        };
    }
}

