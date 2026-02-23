<?php

namespace App\Controller\Admin;

use App\Entity\Announcement;
use App\Repository\CommentaireRepository;
use App\Repository\FeedAiAnalysisRepository;
use App\Repository\PostRepository;
use App\Service\FeedIntelligenceService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Process\Process;

#[Route('/fil/admin/intelligence', name: 'fil_admin_intelligence_')]
#[IsGranted('ROLE_ADMIN')]
class AdminFeedIntelligenceController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(
        PostRepository $postRepository,
        CommentaireRepository $commentaireRepository,
        FeedAiAnalysisRepository $feedAiAnalysisRepository,
        FeedIntelligenceService $feedIntelligenceService
    ): Response {
        $sinceWeek = (new \DateTimeImmutable('now'))->modify('-7 days');
        $recentPosts = $postRepository->findSinceWithMetrics($sinceWeek, 250);
        $highlights = $feedIntelligenceService->buildDailyHighlights($recentPosts, 5);
        $trends = $feedIntelligenceService->detectTrendingTopics($recentPosts, 8);
        $bestTime = $feedIntelligenceService->suggestBestTimeToPost(null, $recentPosts);

        $flaggedPostAnalyses = $feedAiAnalysisRepository->findFlagged('post', 30);
        $flaggedCommentAnalyses = $feedAiAnalysisRepository->findFlagged('comment', 30);

        $flaggedPostIds = array_values(array_filter(array_map(static fn ($a) => $a->getEntityId(), $flaggedPostAnalyses)));
        $flaggedCommentIds = array_values(array_filter(array_map(static fn ($a) => $a->getEntityId(), $flaggedCommentAnalyses)));
        $flaggedPosts = $flaggedPostIds ? $postRepository->findBy(['id' => $flaggedPostIds]) : [];
        $flaggedComments = $flaggedCommentIds ? $commentaireRepository->findBy(['id' => $flaggedCommentIds]) : [];

        $postById = [];
        foreach ($flaggedPosts as $post) {
            $postById[(int) $post->getId()] = $post;
        }

        $commentById = [];
        foreach ($flaggedComments as $comment) {
            $commentById[(int) $comment->getId()] = $comment;
        }

        $postModerationInfo = [];
        foreach ($flaggedPostAnalyses as $analysis) {
            $postModerationInfo[(int) $analysis->getEntityId()] = $feedIntelligenceService->buildModerationExplanation(
                (int) $analysis->getToxicityScore(),
                (int) $analysis->getHateSpeechScore(),
                (int) $analysis->getSpamScore(),
                (int) $analysis->getDuplicateScore(),
                (int) $analysis->getMediaRiskScore(),
                (string) $analysis->getAutoAction(),
                is_array($analysis->getFlags()) ? $analysis->getFlags() : []
            );
        }

        $commentModerationInfo = [];
        foreach ($flaggedCommentAnalyses as $analysis) {
            $commentModerationInfo[(int) $analysis->getEntityId()] = $feedIntelligenceService->buildModerationExplanation(
                (int) $analysis->getToxicityScore(),
                (int) $analysis->getHateSpeechScore(),
                (int) $analysis->getSpamScore(),
                (int) $analysis->getDuplicateScore(),
                (int) $analysis->getMediaRiskScore(),
                (string) $analysis->getAutoAction(),
                is_array($analysis->getFlags()) ? $analysis->getFlags() : []
            );
        }

        return $this->render('admin/intelligence/index.html.twig', [
            'stats' => [
                'postCount' => $postRepository->count([]),
                'commentCount' => $commentaireRepository->count([]),
                'flaggedPosts' => $feedAiAnalysisRepository->countFlagged('post'),
                'flaggedComments' => $feedAiAnalysisRepository->countFlagged('comment'),
            ],
            'highlights' => $highlights,
            'trends' => $trends,
            'bestTime' => $bestTime,
            'flaggedPostAnalyses' => $flaggedPostAnalyses,
            'flaggedCommentAnalyses' => $flaggedCommentAnalyses,
            'postById' => $postById,
            'commentById' => $commentById,
            'postModerationInfo' => $postModerationInfo,
            'commentModerationInfo' => $commentModerationInfo,
        ]);
    }

    #[Route('/reanalyze', name: 'reanalyze', methods: ['POST'])]
    public function reanalyze(
        Request $request,
        PostRepository $postRepository,
        CommentaireRepository $commentaireRepository,
        FeedIntelligenceService $feedIntelligenceService,
        EntityManagerInterface $entityManager
    ): RedirectResponse {
        if (!$this->isCsrfTokenValid('fil_ai_admin_reanalyze', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');

            return $this->redirectToRoute('fil_admin_intelligence_index');
        }

        $since = (new \DateTimeImmutable('now'))->modify('-14 days');
        $posts = $postRepository->findSinceWithMetrics($since, 300);
        foreach ($posts as $post) {
            $author = $post->getAuthor();
            $recentTexts = [];
            if ($author instanceof \App\Entity\User) {
                $recentTexts = $postRepository->findRecentTextsByAuthor($author, 20);
            }
            $feedIntelligenceService->analyzeAndPersistPost($post, $recentTexts, false);
        }

        $comments = $commentaireRepository->createQueryBuilder('c')
            ->leftJoin('c.author', 'a')->addSelect('a')
            ->andWhere('c.createdAt >= :since')
            ->setParameter('since', $since)
            ->orderBy('c.createdAt', 'DESC')
            ->setMaxResults(500)
            ->getQuery()
            ->getResult();
        foreach ($comments as $comment) {
            $author = $comment->getAuthor();
            $recentTexts = [];
            if ($author instanceof \App\Entity\User) {
                $recentTexts = $commentaireRepository->findRecentTextsByAuthor($author, 30);
            }
            $feedIntelligenceService->analyzeAndPersistComment($comment, $recentTexts, false);
        }

        $entityManager->flush();
        $this->addFlash('success', sprintf('Analyse IA locale (Python) rafraichie: %d posts, %d commentaires.', count($posts), count($comments)));

        return $this->redirectToRoute('fil_admin_intelligence_index');
    }

    #[Route('/train-model', name: 'train_model', methods: ['POST'])]
    public function trainModel(Request $request): RedirectResponse
    {
        if (!$this->isCsrfTokenValid('fil_ai_admin_train_model', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');

            return $this->redirectToRoute('fil_admin_intelligence_index');
        }

        $projectDir = (string) $this->getParameter('kernel.project_dir');
        $php = (defined('PHP_BINARY') && is_string(PHP_BINARY) && PHP_BINARY !== '') ? PHP_BINARY : 'php';

        $process = new Process([
            $php,
            $projectDir . '/bin/console',
            'app:feed-ai:train',
            '--no-interaction',
            '--with-web-data=1',
            '--web-max-rows=45000',
            '--web-languages=fr,en,ar',
        ]);
        $process->setTimeout(1800);
        $process->run();

        $combinedOutput = trim($process->getOutput() . "\n" . $process->getErrorOutput());
        $combinedOutput = preg_replace('/\s+/', ' ', $combinedOutput ?? '') ?? '';
        if (strlen($combinedOutput) > 260) {
            $combinedOutput = substr($combinedOutput, 0, 260) . '...';
        }

        if ($process->isSuccessful()) {
            $this->addFlash('success', 'Modele IA local du fil entraine. ' . ($combinedOutput !== '' ? $combinedOutput : 'OK.'));
        } else {
            $this->addFlash('error', 'Echec entrainement IA local du fil. ' . ($combinedOutput !== '' ? $combinedOutput : 'Consultez les logs.'));
        }

        return $this->redirectToRoute('fil_admin_intelligence_index');
    }

    #[Route('/publish-highlights', name: 'publish_highlights', methods: ['POST'])]
    public function publishHighlights(
        Request $request,
        PostRepository $postRepository,
        FeedIntelligenceService $feedIntelligenceService,
        EntityManagerInterface $entityManager
    ): RedirectResponse {
        if (!$this->isCsrfTokenValid('fil_ai_admin_publish_highlights', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');

            return $this->redirectToRoute('fil_admin_intelligence_index');
        }

        $since = (new \DateTimeImmutable('now'))->modify('-1 day');
        $posts = $postRepository->findSinceWithMetrics($since, 120);
        $highlights = $feedIntelligenceService->buildDailyHighlights($posts, 5);

        if ($highlights === []) {
            $this->addFlash('warning', 'Aucun highlight disponible pour aujourd\'hui.');

            return $this->redirectToRoute('fil_admin_intelligence_index');
        }

        $lines = [];
        foreach ($highlights as $index => $item) {
            $lines[] = sprintf(
                "%d. %s (likes: %d, commentaires: %d)",
                $index + 1,
                (string) ($item['title'] ?? 'Publication'),
                (int) ($item['likes'] ?? 0),
                (int) ($item['comments'] ?? 0)
            );
            $summary = trim((string) ($item['summary'] ?? ''));
            if ($summary !== '') {
                $lines[] = '   ' . $summary;
            }
        }

        $announcement = (new Announcement())
            ->setTitle('Top 5 posts du jour')
            ->setTag('highlight')
            ->setContent(implode("\n", $lines))
            ->setMediaType('text')
            ->setCreatedAt(new \DateTimeImmutable());

        $entityManager->persist($announcement);
        $entityManager->flush();
        $this->addFlash('success', 'Highlights du jour publiés dans les annonces.');

        return $this->redirectToRoute('fil_admin_announcement_index');
    }

    #[Route('/publish-trends', name: 'publish_trends', methods: ['POST'])]
    public function publishTrends(
        Request $request,
        PostRepository $postRepository,
        FeedIntelligenceService $feedIntelligenceService,
        EntityManagerInterface $entityManager
    ): RedirectResponse {
        if (!$this->isCsrfTokenValid('fil_ai_admin_publish_trends', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');

            return $this->redirectToRoute('fil_admin_intelligence_index');
        }

        $since = (new \DateTimeImmutable('now'))->modify('-1 day');
        $posts = $postRepository->findSinceWithMetrics($since, 200);
        $trends = $feedIntelligenceService->detectTrendingTopics($posts, 7);

        if ($trends === []) {
            $this->addFlash('warning', 'Aucune tendance détectée pour le moment.');

            return $this->redirectToRoute('fil_admin_intelligence_index');
        }

        $lines = ["Sujets chauds detectes en temps reel :"];
        foreach ($trends as $trend) {
            $lines[] = sprintf('- %s (%d mentions)', (string) $trend['topic'], (int) $trend['count']);
        }

        $announcement = (new Announcement())
            ->setTitle('Alertes tendances')
            ->setTag('trend')
            ->setContent(implode("\n", $lines))
            ->setMediaType('text')
            ->setCreatedAt(new \DateTimeImmutable());

        $entityManager->persist($announcement);
        $entityManager->flush();
        $this->addFlash('success', 'Alerte tendances publiée dans les annonces.');

        return $this->redirectToRoute('fil_admin_announcement_index');
    }
}
