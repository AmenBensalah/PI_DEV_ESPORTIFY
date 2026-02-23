<?php

namespace App\Controller;

use App\Service\FeedIntelligenceService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/fil/ai', name: 'fil_ai_')]
class FeedAiController extends AbstractController
{
    #[Route('/analyze-draft', name: 'analyze_draft', methods: ['POST'])]
    public function analyzeDraft(Request $request, FeedIntelligenceService $feedIntelligenceService): JsonResponse
    {
        if (!$this->isCsrfTokenValid('fil_ai_tools', (string) $request->request->get('_token'))) {
            return new JsonResponse(['error' => 'CSRF token invalide.'], Response::HTTP_BAD_REQUEST);
        }

        $text = trim((string) $request->request->get('text', ''));
        $analysis = $feedIntelligenceService->analyzeRawContent($text, [
            'media_paths' => [],
            'with_ai' => true,
        ]);

        return new JsonResponse([
            'summary' => $analysis['summary_short'] ?? '',
            'category' => $analysis['category'] ?? 'general',
            'hashtags' => $analysis['hashtags'] ?? [],
            'moderation' => [
                'action' => $analysis['auto_action'] ?? 'allow',
                'toxicity' => $analysis['toxicity_score'] ?? 0,
                'spam' => $analysis['spam_score'] ?? 0,
                'hate' => $analysis['hate_speech_score'] ?? 0,
                'risk' => $analysis['risk_label'] ?? 'faible',
                'reason' => $analysis['block_reason'] ?? '',
                'tip' => $analysis['blocking_tip'] ?? '',
            ],
        ]);
    }

    #[Route('/write-assistant', name: 'write_assistant', methods: ['POST'])]
    public function writeAssistant(Request $request, FeedIntelligenceService $feedIntelligenceService): JsonResponse
    {
        if (!$this->isCsrfTokenValid('fil_ai_tools', (string) $request->request->get('_token'))) {
            return new JsonResponse(['error' => 'CSRF token invalide.'], Response::HTTP_BAD_REQUEST);
        }

        $text = trim((string) $request->request->get('text', ''));
        $mode = trim((string) $request->request->get('mode', 'pro'));
        if ($text === '') {
            return new JsonResponse(['error' => 'Texte vide.'], Response::HTTP_BAD_REQUEST);
        }

        $result = $feedIntelligenceService->rewriteText($text, $mode);

        return new JsonResponse($result);
    }

    #[Route('/hashtags', name: 'hashtags', methods: ['POST'])]
    public function hashtags(Request $request, FeedIntelligenceService $feedIntelligenceService): JsonResponse
    {
        if (!$this->isCsrfTokenValid('fil_ai_tools', (string) $request->request->get('_token'))) {
            return new JsonResponse(['error' => 'CSRF token invalide.'], Response::HTTP_BAD_REQUEST);
        }

        $text = trim((string) $request->request->get('text', ''));
        if ($text === '') {
            return new JsonResponse(['hashtags' => []]);
        }

        return new JsonResponse([
            'hashtags' => $feedIntelligenceService->generateHashtags($text),
        ]);
    }

    #[Route('/translate', name: 'translate', methods: ['POST'])]
    public function translate(Request $request, FeedIntelligenceService $feedIntelligenceService): JsonResponse
    {
        if (!$this->isCsrfTokenValid('fil_ai_tools', (string) $request->request->get('_token'))) {
            return new JsonResponse(['error' => 'CSRF token invalide.'], Response::HTTP_BAD_REQUEST);
        }

        $text = trim((string) $request->request->get('text', ''));
        $target = trim((string) $request->request->get('target', 'en'));
        $entityType = trim((string) $request->request->get('entityType', ''));
        $entityId = (int) $request->request->get('entityId', 0);
        $force = in_array(strtolower(trim((string) $request->request->get('force', '0'))), ['1', 'true', 'yes'], true);

        if ($text === '') {
            return new JsonResponse(['translated' => '']);
        }

        try {
            $translated = ($entityType !== '' && $entityId > 0)
                ? $feedIntelligenceService->translateEntityText($entityType, $entityId, $text, $target, $force)
                : $feedIntelligenceService->translateText($text, $target);
        } catch (\Throwable) {
            $translated = $text;
        }

        return new JsonResponse([
            'translated' => $translated,
            'target' => strtolower($target),
        ]);
    }

    #[Route('/best-time', name: 'best_time', methods: ['GET'])]
    public function bestTime(FeedIntelligenceService $feedIntelligenceService): JsonResponse
    {
        $user = $this->getUser();
        $result = $feedIntelligenceService->suggestBestTimeToPost(
            $user instanceof \App\Entity\User ? $user : null
        );

        return new JsonResponse($result);
    }
}
