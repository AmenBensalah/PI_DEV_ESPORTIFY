<?php

namespace App\Service;

use App\Entity\Commentaire;
use App\Entity\FeedAiAnalysis;
use App\Entity\Post;
use App\Entity\User;
use App\Repository\EquipeRepository;
use App\Repository\FeedAiAnalysisRepository;
use App\Repository\PostRepository;
use Doctrine\ORM\EntityManagerInterface;

class FeedIntelligenceService
{
    /**
     * @var string[]
     */
    private array $stopWords = [
        'the', 'and', 'for', 'avec', 'dans', 'pour', 'mais', 'donc', 'alors', 'des', 'les', 'une', 'que', 'qui',
        'sur', 'this', 'that', 'from', 'vous', 'nous', 'ils', 'elles', 'tout', 'tous', 'your', 'their', 'avec',
        'sans', 'plus', 'moins', 'have', 'has', 'had', 'are', 'est', 'etre', 'ete', 'avait', 'ÃƒÆ’Ã‚Âªtre', 'ÃƒÆ’Ã‚Â©tÃƒÆ’Ã‚Â©',
        'http', 'https', 'www', 'com', 'dans', 'depuis', 'hier', 'aujourd', 'demain',
    ];

    /**
     * @var array<string,int>
     */
    private array $toxicKeywords = [
        'idiot' => 16, 'imbecile' => 16, 'stupide' => 14, 'nul' => 8, 'loser' => 10, 'degage' => 14,
        'shut up' => 12, 'trash' => 12, 'noob' => 8, 'clown' => 8, 'hate' => 12, 'fuck' => 15,
        'fck' => 12, 'wtf' => 8, 'merde' => 12, 'connard' => 18, 'pute' => 18, 'batard' => 18,
    ];

    /**
     * @var array<string,int>
     */
    private array $hateKeywords = [
        'raciste' => 40, 'racism' => 40, 'nazi' => 50, 'terroriste' => 45, 'sale arabe' => 60,
        'sale noir' => 60, 'dirty arab' => 60, 'dirty black' => 60, 'go back to your country' => 55,
        'genocide' => 45, 'kill all' => 55, 'hate speech' => 35,
    ];

    /**
     * @var array<string,int>
     */
    private array $spamKeywords = [
        'buy now' => 20, 'promotion' => 14, 'promo' => 12, 'discount' => 14, 'free money' => 30,
        'bitcoin' => 14, 'crypto' => 10, 'casino' => 20, 'bonus' => 12, 'click here' => 18,
        'subscribe' => 8, 'followers' => 12, 'pub' => 10, 'publicite' => 16, 'offre limitee' => 16,
    ];

    /**
     * @var array<string,string[]>
     */
    private array $categoryKeywords = [
        'tournoi' => ['tournoi', 'tournament', 'bracket', 'finale', 'match', 'league', 'cup'],
        'recrutement' => ['recrutement', 'tryout', 'join team', 'we need', 'searching player', 'lft', 'lfp'],
        'resultat' => ['resultat', 'score', 'won', 'lost', 'victoire', 'defaite', 'mvp', 'classement'],
        'annonce' => ['annonce', 'announcement', 'update', 'news', 'mise a jour', 'nouveau'],
        'drama' => ['drama', 'clash', 'beef', 'toxic', 'scandal', 'controverse'],
        'event' => ['event', 'evenement', 'lan', 'bootcamp', 'meetup'],
    ];

    public function __construct(
        private FeedAiPythonService $feedAiPythonService,
        private FeedAiAnalysisRepository $feedAiAnalysisRepository,
        private EntityManagerInterface $entityManager,
        private PostRepository $postRepository,
        private EquipeRepository $equipeRepository
    ) {
    }

    /**
     * @param array<string,mixed> $context
     * @return array<string,mixed>
     */
    public function analyzeRawContent(string $text, array $context = []): array
    {
        $normalized = $this->normalizeText($text);
        $existingTexts = $context['existing_texts'] ?? [];
        $mediaPaths = $context['media_paths'] ?? [];
        $withAi = (bool) ($context['with_ai'] ?? false);

        $ml = $this->feedAiPythonService->runTask('analyze_full', [
            'text' => $normalized,
            'existing_texts' => is_array($existingTexts) ? $existingTexts : [],
            'media_paths' => is_array($mediaPaths) ? $mediaPaths : [],
            'with_ai' => $withAi,
        ]);
        if (is_array($ml) && isset($ml['auto_action'])) {
            $autoAction = (string) ($ml['auto_action'] ?? 'allow');
            $toxicityScore = $this->clampScore((int) ($ml['toxicity_score'] ?? 0));
            $hateSpeechScore = $this->clampScore((int) ($ml['hate_speech_score'] ?? 0));
            $spamScore = $this->clampScore((int) ($ml['spam_score'] ?? 0));
            $duplicateScore = $this->clampScore((int) ($ml['duplicate_score'] ?? 0));
            $mediaRiskScore = $this->clampScore((int) ($ml['media_risk_score'] ?? 0));
            $hashtags = is_array($ml['hashtags'] ?? null) ? $this->normalizeHashtags($ml['hashtags']) : [];
            $flags = is_array($ml['flags'] ?? null)
                ? array_values(array_filter($ml['flags'], static fn ($v): bool => is_string($v) && $v !== ''))
                : $this->buildFlags($toxicityScore, $hateSpeechScore, $spamScore, $duplicateScore, $mediaRiskScore, $autoAction);
            $explanation = $this->buildModerationExplanation(
                $toxicityScore,
                $hateSpeechScore,
                $spamScore,
                $duplicateScore,
                $mediaRiskScore,
                $autoAction,
                $flags
            );

            $summaryShort = $this->finalizeAiText((string) ($ml['summary_short'] ?? ''), true);
            $summaryLong = $this->finalizeAiText((string) ($ml['summary_long'] ?? ''), true);

            return [
                'summary_short' => mb_substr($summaryShort, 0, 180),
                'summary_long' => mb_substr($summaryLong, 0, 360),
                'hashtags' => $hashtags,
                'category' => mb_substr((string) ($ml['category'] ?? 'general'), 0, 50),
                'toxicity_score' => $toxicityScore,
                'hate_speech_score' => $hateSpeechScore,
                'spam_score' => $spamScore,
                'duplicate_score' => $duplicateScore,
                'media_risk_score' => $mediaRiskScore,
                'auto_action' => $autoAction,
                'flags' => $flags,
                'risk_label' => (string) ($ml['risk_label'] ?? $this->buildRiskLabel($autoAction, $toxicityScore, $spamScore, $hateSpeechScore)),
                'block_reason' => (string) (($ml['block_reason'] ?? '') ?: $explanation['reason']),
                'blocking_tip' => (string) (($ml['blocking_tip'] ?? '') ?: $explanation['tip']),
            ];
        }

        $fallbackExplanation = $this->buildModerationExplanation(0, 0, 0, 0, 0, 'allow', ['ml_unavailable']);
        return [
            'summary_short' => mb_substr($normalized, 0, 180),
            'summary_long' => mb_substr($normalized, 0, 360),
            'hashtags' => [],
            'category' => 'general',
            'toxicity_score' => 0,
            'hate_speech_score' => 0,
            'spam_score' => 0,
            'duplicate_score' => 0,
            'media_risk_score' => 0,
            'auto_action' => 'allow',
            'flags' => ['ml_unavailable'],
            'risk_label' => 'faible',
            'block_reason' => $fallbackExplanation['reason'],
            'blocking_tip' => $fallbackExplanation['tip'],
        ];
    }

    /**
     * @param string[] $flags
     * @return array{reason:string,tip:string}
     */
    public function buildModerationExplanation(
        int $toxicity,
        int $hate,
        int $spam,
        int $duplicate,
        int $mediaRisk,
        string $autoAction,
        array $flags = []
    ): array {
        $scores = [
            'hate' => $hate,
            'toxicity' => $toxicity,
            'spam' => $spam,
            'duplicate' => $duplicate,
            'media' => $mediaRisk,
        ];
        arsort($scores);
        $main = (string) array_key_first($scores);
        $mainScore = (int) ($scores[$main] ?? 0);

        $reason = 'Contenu autorise: aucun signal critique detecte.';
        $tip = 'Aucune action requise.';

        if (in_array('ml_unavailable', $flags, true)) {
            return [
                'reason' => 'Analyse locale indisponible: decision prise en mode degrade.',
                'tip' => 'Verifier le service IA Python et relancer la reanalyse.',
            ];
        }

        if ($autoAction === 'allow') {
            return ['reason' => $reason, 'tip' => $tip];
        }

        if ($main === 'hate') {
            $reason = sprintf('Blocage pour discours haineux suspect (score: %d/100).', $mainScore);
            $tip = 'Supprimer les insultes discriminatoires et reformuler de facon neutre.';
        } elseif ($main === 'toxicity') {
            $reason = sprintf('Blocage pour agressivite/toxicite elevee (score: %d/100).', $mainScore);
            $tip = 'Retirer insultes et attaques personnelles, adopter un ton professionnel.';
        } elseif ($main === 'spam') {
            $reason = sprintf('Blocage pour spam/promotion excessive (score: %d/100).', $mainScore);
            $tip = 'Reduire liens/publicite repetee et publier un message plus naturel.';
        } elseif ($main === 'duplicate') {
            $reason = sprintf('Blocage pour doublon probable (score: %d/100).', $mainScore);
            $tip = 'Modifier le texte, ajouter du contexte utile et eviter les reposts identiques.';
        } elseif ($main === 'media') {
            $reason = sprintf('Blocage pour media suspect (score: %d/100).', $mainScore);
            $tip = 'Remplacer le media par un fichier propre et conforme aux regles.';
        }

        if ($autoAction === 'review') {
            $reason = str_replace('Blocage', 'Alerte moderation', $reason);
            $tip = 'Contenu a verifier manuellement avant validation definitive.';
        }

        return ['reason' => $reason, 'tip' => $tip];
    }

    public function analyzeAndPersistPost(Post $post, array $existingTexts = [], bool $flush = true): FeedAiAnalysis
    {
        $content = trim((string) $post->getContent());
        $eventPart = trim((string) $post->getEventTitle() . ' ' . $post->getEventLocation());
        $sourceText = trim($content . ' ' . $eventPart);

        $mediaPaths = [];
        if ($post->getImagePath()) {
            $mediaPaths[] = (string) $post->getImagePath();
        }
        if ($post->getVideoUrl()) {
            $mediaPaths[] = (string) $post->getVideoUrl();
        }
        foreach ($post->getMedias() as $media) {
            $mediaPaths[] = (string) $media->getPath();
        }

        $analysis = $this->analyzeRawContent($sourceText, [
            'existing_texts' => $existingTexts,
            'media_paths' => $mediaPaths,
            'with_ai' => true,
        ]);

        return $this->persistAnalysis('post', (int) $post->getId(), $sourceText, $analysis, $flush);
    }

    public function analyzeAndPersistComment(Commentaire $comment, array $existingTexts = [], bool $flush = true): FeedAiAnalysis
    {
        $sourceText = trim((string) $comment->getContent());
        $analysis = $this->analyzeRawContent($sourceText, [
            'existing_texts' => $existingTexts,
            'media_paths' => [],
            'with_ai' => true,
        ]);

        return $this->persistAnalysis('comment', (int) $comment->getId(), $sourceText, $analysis, $flush);
    }

    /**
     * @param array<string,mixed> $analysis
     */
    public function persistAnalysis(string $entityType, int $entityId, string $sourceText, array $analysis, bool $flush = true): FeedAiAnalysis
    {
        $record = $this->feedAiAnalysisRepository->findOneForEntity($entityType, $entityId);
        if (!$record) {
            $record = new FeedAiAnalysis();
            $record->setEntityType($entityType);
            $record->setEntityId($entityId);
        }

        $record
            ->setSourceHash(hash('sha256', $this->normalizeText($sourceText)))
            ->setSummaryShort($analysis['summary_short'] ?? null)
            ->setSummaryLong($analysis['summary_long'] ?? null)
            ->setHashtags($analysis['hashtags'] ?? null)
            ->setCategory($analysis['category'] ?? null)
            ->setToxicityScore((int) ($analysis['toxicity_score'] ?? 0))
            ->setHateSpeechScore((int) ($analysis['hate_speech_score'] ?? 0))
            ->setSpamScore((int) ($analysis['spam_score'] ?? 0))
            ->setDuplicateScore((int) ($analysis['duplicate_score'] ?? 0))
            ->setMediaRiskScore((int) ($analysis['media_risk_score'] ?? 0))
            ->setAutoAction((string) ($analysis['auto_action'] ?? 'allow'))
            ->setFlags(is_array($analysis['flags'] ?? null) ? $analysis['flags'] : [])
            ->setUpdatedAt(new \DateTimeImmutable());

        $this->entityManager->persist($record);
        if ($flush) {
            $this->entityManager->flush();
        }

        return $record;
    }

    /**
     * @param Post[] $posts
     * @return array<int, FeedAiAnalysis>
     */
    public function ensurePostAnalyses(array $posts, int $maxNewAnalyses = 25): array
    {
        $ids = array_values(array_filter(array_map(static fn (Post $post) => $post->getId(), $posts)));
        $map = $this->feedAiAnalysisRepository->findMapForEntities('post', $ids);

        $created = 0;
        foreach ($posts as $post) {
            $postId = $post->getId();
            if ($postId === null || isset($map[$postId])) {
                continue;
            }
            if ($created >= $maxNewAnalyses) {
                break;
            }

            $author = $post->getAuthor();
            $recentTexts = [];
            if ($author instanceof User) {
                $recentTexts = $this->postRepository->findRecentTextsByAuthor($author, 20);
            }

            $map[$postId] = $this->analyzeAndPersistPost($post, $recentTexts, false);
            $created++;
        }

        if ($created > 0) {
            $this->entityManager->flush();
        }

        return $map;
    }

    /**
     * @param Commentaire[] $comments
     * @return array<int, FeedAiAnalysis>
     */
    public function ensureCommentAnalyses(array $comments, int $maxNewAnalyses = 40): array
    {
        $ids = array_values(array_filter(array_map(static fn (Commentaire $comment) => $comment->getId(), $comments)));
        $map = $this->feedAiAnalysisRepository->findMapForEntities('comment', $ids);

        $created = 0;
        foreach ($comments as $comment) {
            $commentId = $comment->getId();
            if ($commentId === null || isset($map[$commentId])) {
                continue;
            }
            if ($created >= $maxNewAnalyses) {
                break;
            }
            $map[$commentId] = $this->analyzeAndPersistComment($comment, [], false);
            $created++;
        }

        if ($created > 0) {
            $this->entityManager->flush();
        }

        return $map;
    }

    /**
     * @param Post[] $posts
     * @param array<int,FeedAiAnalysis> $analysisMap
     * @return Post[]
     */
    public function sortPostsForUser(array $posts, ?User $user, array $analysisMap = []): array
    {
        if ($user === null) {
            usort($posts, static function (Post $a, Post $b): int {
                $left = $a->getCreatedAt()?->getTimestamp() ?? 0;
                $right = $b->getCreatedAt()?->getTimestamp() ?? 0;

                return $right <=> $left;
            });

            return $posts;
        }

        $interestTokens = $this->buildUserInterestTokens($user);

        usort($posts, function (Post $a, Post $b) use ($analysisMap, $interestTokens, $user): int {
            $scoreA = $this->computePostScore($a, $analysisMap[$a->getId() ?? -1] ?? null, $interestTokens, $user);
            $scoreB = $this->computePostScore($b, $analysisMap[$b->getId() ?? -1] ?? null, $interestTokens, $user);

            if ($scoreA === $scoreB) {
                $left = $a->getCreatedAt()?->getTimestamp() ?? 0;
                $right = $b->getCreatedAt()?->getTimestamp() ?? 0;

                return $right <=> $left;
            }

            return $scoreB <=> $scoreA;
        });

        return $posts;
    }

    /**
     * @param Post[] $posts
     * @return array<int,array<string,mixed>>
     */
    public function buildDailyHighlights(array $posts, int $limit = 5): array
    {
        $rows = [];
        foreach ($posts as $post) {
            $engagement = ($post->getLikes()->count() * 2) + ($post->getCommentaires()->count() * 3) + ($post->getParticipantsCount() * 2);
            $hours = max(1, (int) floor((time() - ($post->getCreatedAt()?->getTimestamp() ?? time())) / 3600));
            $recency = max(1, 36 - $hours);
            $score = $engagement + $recency;

            $title = $post->isEvent()
                ? ((string) ($post->getEventTitle() ?: 'Evenement'))
                : $this->summarizeText((string) $post->getContent(), 95);
            $summary = $this->summarizeText((string) $post->getContent(), 170);

            $rows[] = [
                'post' => $post,
                'score' => $score,
                'title' => $title !== '' ? $title : 'Publication',
                'summary' => $summary !== '' ? $summary : 'Publication populaire du jour',
                'likes' => $post->getLikes()->count(),
                'comments' => $post->getCommentaires()->count(),
            ];
        }

        usort($rows, static fn (array $a, array $b): int => ($b['score'] <=> $a['score']));

        return array_slice($rows, 0, max(1, $limit));
    }

    /**
     * @param Post[] $posts
     * @return array<int,array{topic:string,count:int}>
     */
    public function detectTrendingTopics(array $posts, int $limit = 5): array
    {
        $counter = [];
        foreach ($posts as $post) {
            $text = trim((string) $post->getContent() . ' ' . (string) $post->getEventTitle() . ' ' . (string) $post->getEventLocation());
            if ($text === '') {
                continue;
            }
            $tokens = $this->tokenize($text);
            foreach ($tokens as $token) {
                if (mb_strlen($token) < 4 || in_array($token, $this->stopWords, true)) {
                    continue;
                }
                $counter[$token] = ($counter[$token] ?? 0) + 1;
            }
        }

        arsort($counter);
        $trends = [];
        foreach (array_slice($counter, 0, max(1, $limit), true) as $topic => $count) {
            $trends[] = [
                'topic' => $topic,
                'count' => $count,
            ];
        }

        return $trends;
    }

    /**
     * @param Post[]|null $posts
     * @return array{hour:int,label:string,reason:string}
     */
    public function suggestBestTimeToPost(?User $user = null, ?array $posts = null): array
    {
        $samples = $posts;
        if (!is_array($samples)) {
            $since = (new \DateTimeImmutable('now'))->modify('-30 days');
            $samples = $this->postRepository->findSinceWithMetrics($since, 600);
        }

        if ($samples === []) {
            return [
                'hour' => 20,
                'label' => '20:00',
                'reason' => 'Pas assez de donnees, recommandation par defaut en soiree.',
            ];
        }

        $interestTokens = $user ? $this->buildUserInterestTokens($user) : [];

        $scoresByHour = array_fill(0, 24, 0.0);
        foreach ($samples as $post) {
            if (!$post instanceof Post || !$post->getCreatedAt()) {
                continue;
            }
            $hour = (int) $post->getCreatedAt()->format('G');
            $engagement = ($post->getLikes()->count() * 2) + ($post->getCommentaires()->count() * 3) + ($post->getParticipantsCount() * 2) + 1;

            $text = mb_strtolower(trim((string) $post->getContent() . ' ' . (string) $post->getEventTitle()));
            $profileBoost = 1.0;
            if ($interestTokens !== []) {
                $matches = 0;
                foreach ($interestTokens as $token) {
                    if (mb_strlen($token) >= 3 && str_contains($text, $token)) {
                        $matches++;
                    }
                }
                $profileBoost += min(0.8, $matches * 0.1);
            }

            $scoresByHour[$hour] += $engagement * $profileBoost;
        }

        $bestHour = 0;
        $bestScore = -1.0;
        foreach ($scoresByHour as $hour => $score) {
            if ($score > $bestScore) {
                $bestScore = $score;
                $bestHour = (int) $hour;
            }
        }

        return [
            'hour' => $bestHour,
            'label' => str_pad((string) $bestHour, 2, '0', STR_PAD_LEFT) . ':00',
            'reason' => 'Heure estimee selon les pics d engagement recents de votre audience.',
        ];
    }

    /**
     * @param array<string,mixed> $options
     * @return string[]
     */
    public function generateHashtags(string $text, array $options = []): array
    {
        $local = $this->generateHashtagsFromText($text, $options);
        $ml = $this->feedAiPythonService->runTask('hashtags', [
            'text' => $text,
            'options' => $options,
        ]);
        if (is_array($ml) && isset($ml['hashtags']) && is_array($ml['hashtags'])) {
            return $this->normalizeHashtags(array_merge($local, $ml['hashtags']));
        }

        return $local;
    }

    /**
     * @return array{output:string,mode:string}
     */
    public function rewriteText(string $text, string $mode = 'pro'): array
    {
        $input = trim($text);
        if ($input === '') {
            return ['output' => '', 'mode' => $mode];
        }

        $ml = $this->feedAiPythonService->runTask('rewrite', [
            'text' => $input,
            'mode' => $mode,
        ]);
        if (is_array($ml) && isset($ml['output']) && is_string($ml['output'])) {
            $candidate = $this->finalizeAiText((string) $ml['output'], true);
            if ($candidate !== '' && !$this->isAiErrorText($candidate)) {
                return ['output' => $candidate, 'mode' => $mode];
            }
        }

        $output = $input;
        if ($mode === 'correct' || $mode === 'pro') {
            $output = preg_replace('/\s+/u', ' ', $output) ?? $output;
            $output = ucfirst(trim($output));
            if ($output !== '' && !preg_match('/[.!?]$/u', $output)) {
                $output .= '.';
            }
        }
        if ($mode === 'short') {
            $output = $this->summarizeText($output, 180);
        }
        if ($mode === 'long') {
            $output = rtrim($output, '.') . ".\n\nN'hesitez pas a donner votre avis et partager vos retours en commentaire.";
        }

        return ['output' => $this->finalizeAiText($output, true), 'mode' => $mode];
    }

    public function translateText(string $text, string $targetLang): string
    {
        $input = trim($text);
        $target = strtolower(trim($targetLang));
        if ($input === '') {
            return '';
        }
        if (!in_array($target, ['fr', 'en', 'ar'], true)) {
            return $input;
        }

        $ml = $this->feedAiPythonService->runTask('translate', [
            'text' => $input,
            'target' => $target,
        ]);
        if (is_array($ml) && isset($ml['translated']) && is_string($ml['translated'])) {
            $candidate = $this->finalizeAiText((string) $ml['translated'], $target !== 'ar');
            if ($candidate !== '' && !$this->isAiErrorText($candidate)) {
                return $candidate;
            }
        }

        return $this->finalizeAiText($input, false);
    }

    public function translateEntityText(string $entityType, int $entityId, string $sourceText, string $targetLang, bool $forceRecompute = false): string
    {
        $target = strtolower(trim($targetLang));
        $source = trim($sourceText);
        if ($source === '' || !in_array($target, ['fr', 'en', 'ar'], true)) {
            return $source;
        }

        $analysis = $this->feedAiAnalysisRepository->findOneForEntity($entityType, $entityId);
        if (!$analysis) {
            $analysis = new FeedAiAnalysis();
            $analysis->setEntityType($entityType)->setEntityId($entityId);
        }

        $translations = is_array($analysis->getTranslations()) ? $analysis->getTranslations() : [];
        if (!$forceRecompute && isset($translations[$target]) && is_string($translations[$target]) && $translations[$target] !== '') {
            $cached = trim($translations[$target]);
            if (
                !$this->isAiErrorText($cached)
                && !$this->isPlaceholderTranslation($cached, $target, $source)
                && $cached !== ''
                && $cached !== $source
            ) {
                return $cached;
            }
        }

        $translated = $this->translateText($source, $target);
        if (trim($translated) === '' || trim($translated) === $source || $this->isAiErrorText($translated)) {
            return $source;
        }
        $translations[$target] = $translated;
        $analysis->setTranslations($translations)->setUpdatedAt(new \DateTimeImmutable());
        if (!$analysis->getSourceHash()) {
            $analysis->setSourceHash(hash('sha256', $this->normalizeText($source)));
        }

        $this->entityManager->persist($analysis);
        $this->entityManager->flush();

        return $translated;
    }

    private function isPlaceholderTranslation(string $translated, string $target, string $source): bool
    {
        $value = trim($translated);
        if ($value === '' || $value === trim($source)) {
            return true;
        }
        $prefix = strtolower(trim($target)) . ':';

        return str_starts_with(strtolower($value), $prefix);
    }

    private function isAiErrorText(string $value): bool
    {
        $normalized = strtoupper(trim($value));
        if ($normalized === '') {
            return true;
        }

        return str_starts_with($normalized, 'ERREUR')
            || str_starts_with($normalized, 'ERROR')
            || str_contains($normalized, 'ERREUR_GEMINI')
            || str_contains($normalized, 'NEXUS_AI EST TEMPORAIREMENT HORS LIGNE');
    }

    /**
     * @param string[] $existingTexts
     */
    private function computeDuplicateScore(string $text, array $existingTexts): int
    {
        $value = trim($text);
        if ($value === '' || $existingTexts === []) {
            return 0;
        }

        $max = 0.0;
        $current = mb_strtolower($value);
        foreach ($existingTexts as $existingText) {
            $candidate = mb_strtolower(trim((string) $existingText));
            if ($candidate === '') {
                continue;
            }
            similar_text($current, $candidate, $pct);
            if ($pct > $max) {
                $max = $pct;
            }
        }

        return (int) round(min(100, $max));
    }

    /**
     * @param string[] $mediaPaths
     */
    private function computeMediaRisk(array $mediaPaths): int
    {
        $risk = 0;
        foreach ($mediaPaths as $path) {
            $lower = mb_strtolower((string) $path);
            if ($lower === '') {
                continue;
            }
            if (preg_match('/(nsfw|xxx|gore|blood|weapon|violence|hate)/i', $lower)) {
                $risk += 30;
            }
            if (preg_match('/\.(exe|bat|cmd|ps1|dll|scr)(\?.*)?$/i', $lower)) {
                $risk += 40;
            }
            if (preg_match('/\.(php|phtml|js)(\?.*)?$/i', $lower)) {
                $risk += 25;
            }
        }

        return $this->clampScore($risk);
    }

    /**
     * @param array<string,int> $keywords
     */
    private function scoreKeywords(string $lowerText, array $keywords): int
    {
        $score = 0;
        foreach ($keywords as $keyword => $weight) {
            $hits = substr_count($lowerText, $keyword);
            if ($hits <= 0) {
                continue;
            }
            $score += $hits * $weight;
        }

        return $score;
    }

    private function computeCapsRatio(string $text): float
    {
        $letters = preg_replace('/[^[:alpha:]]/u', '', $text) ?? '';
        if ($letters === '') {
            return 0.0;
        }
        preg_match_all('/[A-Z]/u', $letters, $matches);

        return count($matches[0]) / max(1, mb_strlen($letters));
    }

    private function clampScore(int $score): int
    {
        return max(0, min(100, $score));
    }

    private function categorizeContent(string $lowerText): string
    {
        $bestCategory = 'general';
        $bestScore = 0;
        foreach ($this->categoryKeywords as $category => $keywords) {
            $score = 0;
            foreach ($keywords as $keyword) {
                if (str_contains($lowerText, $keyword)) {
                    $score++;
                }
            }
            if ($score > $bestScore) {
                $bestScore = $score;
                $bestCategory = $category;
            }
        }

        return $bestCategory;
    }

    private function decideAutoAction(int $toxicity, int $hate, int $spam, int $duplicate, int $mediaRisk): string
    {
        if ($hate >= 75 || $toxicity >= 85 || $spam >= 92 || $duplicate >= 94 || $mediaRisk >= 80) {
            return 'block';
        }
        if ($hate >= 55 || $toxicity >= 65 || $spam >= 70 || $duplicate >= 75 || $mediaRisk >= 55) {
            return 'review';
        }

        return 'allow';
    }

    /**
     * @return string[]
     */
    private function buildFlags(int $toxicity, int $hate, int $spam, int $duplicate, int $mediaRisk, string $autoAction): array
    {
        $flags = [];
        if ($toxicity >= 60) {
            $flags[] = 'toxicite_elevee';
        }
        if ($hate >= 50) {
            $flags[] = 'hate_speech_suspect';
        }
        if ($spam >= 70) {
            $flags[] = 'spam_probable';
        }
        if ($duplicate >= 75) {
            $flags[] = 'doublon_probable';
        }
        if ($mediaRisk >= 55) {
            $flags[] = 'media_suspect';
        }
        if ($autoAction === 'block') {
            $flags[] = 'action_blocage_auto';
        }
        if ($autoAction === 'review') {
            $flags[] = 'alerte_moderation';
        }

        return array_values(array_unique($flags));
    }

    private function buildRiskLabel(string $action, int $toxicity, int $spam, int $hate): string
    {
        if ($action === 'block') {
            return 'critique';
        }
        if ($action === 'review') {
            return 'eleve';
        }
        $max = max($toxicity, $spam, $hate);
        if ($max >= 40) {
            return 'moyen';
        }

        return 'faible';
    }

    private function summarizeText(string $text, int $maxChars): string
    {
        $clean = trim(preg_replace('/\s+/u', ' ', $text) ?? '');
        if ($clean === '') {
            return '';
        }
        if (mb_strlen($clean) <= $maxChars) {
            return $clean;
        }

        $snippet = mb_substr($clean, 0, max(20, $maxChars - 1));
        $lastSpace = mb_strrpos($snippet, ' ');
        if ($lastSpace !== false && $lastSpace > 20) {
            $snippet = mb_substr($snippet, 0, $lastSpace);
        }

        return rtrim($snippet, " \t\n\r\0\x0B.,;:!?") . '...';
    }

    /**
     * @param array<string,mixed> $options
     * @return string[]
     */
    private function generateHashtagsFromText(string $text, array $options = []): array
    {
        $tokens = $this->tokenize($text);
        $counter = [];
        foreach ($tokens as $token) {
            if (mb_strlen($token) < 4 || in_array($token, $this->stopWords, true)) {
                continue;
            }
            $counter[$token] = ($counter[$token] ?? 0) + 1;
        }
        arsort($counter);

        $tags = [];
        if (!empty($options['category']) && is_string($options['category']) && $options['category'] !== 'general') {
            $tags[] = '#' . $this->slugifyTag($options['category']);
        }
        foreach (array_slice(array_keys($counter), 0, 5) as $token) {
            $tags[] = '#' . $this->slugifyTag($token);
        }
        $tags[] = '#esport';

        if (!empty($options['media_paths']) && is_array($options['media_paths'])) {
            $joined = mb_strtolower(implode(' ', array_map('strval', $options['media_paths'])));
            if (str_contains($joined, '.mp4') || str_contains($joined, 'video')) {
                $tags[] = '#video';
            }
            if (str_contains($joined, '.jpg') || str_contains($joined, '.png') || str_contains($joined, 'image')) {
                $tags[] = '#image';
            }
        }

        return $this->normalizeHashtags($tags);
    }

    /**
     * @param string[] $hashtags
     * @return string[]
     */
    private function normalizeHashtags(array $hashtags): array
    {
        $clean = [];
        foreach ($hashtags as $tag) {
            $value = trim((string) $tag);
            if ($value === '') {
                continue;
            }
            if (!str_starts_with($value, '#')) {
                $value = '#' . $value;
            }
            $value = '#' . preg_replace('/[^a-z0-9_]+/i', '', ltrim($value, '#'));
            if ($value === '#') {
                continue;
            }
            $clean[] = mb_strtolower($value);
        }

        return array_slice(array_values(array_unique($clean)), 0, 8);
    }

    private function slugifyTag(string $value): string
    {
        $lower = mb_strtolower(trim($value));
        $slug = preg_replace('/[^a-z0-9_]+/i', '', $lower);

        return $slug ?: 'tag';
    }

    private function normalizeText(string $text): string
    {
        $clean = strtr($text, [
            'Ã©' => 'é', 'Ã¨' => 'è', 'Ãª' => 'ê', 'Ã«' => 'ë', 'Ã ' => 'à', 'Ã¢' => 'â',
            'Ã®' => 'î', 'Ã¯' => 'ï', 'Ã´' => 'ô', 'Ã¶' => 'ö', 'Ã¹' => 'ù', 'Ã»' => 'û',
            'Ã¼' => 'ü', 'Ã§' => 'ç', 'â€™' => "'", 'â€˜' => "'", 'â€œ' => '"', 'â€' => '"',
            'â€¦' => '...', 'â€“' => '-', 'â€”' => '-',
        ]);
        $clean = trim((string) preg_replace('/\s+/u', ' ', $clean));

        return $clean;
    }

    private function finalizeAiText(string $text, bool $forceEndingPunctuation = false): string
    {
        $value = $this->normalizeText($text);
        if ($value === '') {
            return '';
        }

        $value = preg_replace('/\s+([,.;:!?])/u', '$1', $value) ?? $value;
        $value = preg_replace('/([,.;:!?])([^\s])/u', '$1 $2', $value) ?? $value;
        $value = trim((string) preg_replace('/\s{2,}/u', ' ', $value));

        if ($value !== '') {
            $first = mb_substr($value, 0, 1);
            $value = mb_strtoupper($first) . mb_substr($value, 1);
        }

        if ($forceEndingPunctuation && $value !== '' && !preg_match('/[.!?]$/u', $value)) {
            $value .= '.';
        }

        return $value;
    }

    /**
     * @return string[]
     */
    private function tokenize(string $text): array
    {
        $lower = mb_strtolower($text);
        $parts = preg_split('/[^a-z0-9\x{0600}-\x{06FF}]+/ui', $lower) ?: [];

        return array_values(array_filter($parts, static fn (string $token) => $token !== ''));
    }

    /**
     * @param string[] $interestTokens
     */
    private function computePostScore(Post $post, ?FeedAiAnalysis $analysis, array $interestTokens, User $user): int
    {
        $hoursSince = (int) floor((time() - ($post->getCreatedAt()?->getTimestamp() ?? time())) / 3600);
        $recency = max(0, 120 - ($hoursSince * 3));
        $engagement = ($post->getLikes()->count() * 4) + ($post->getCommentaires()->count() * 5) + ($post->getParticipantsCount() * 3);
        $score = $recency + $engagement;

        if ($post->isEvent()) {
            $score += 8;
        }
        if ($post->getAuthor()?->getId() === $user->getId()) {
            $score += 6;
        }

        $text = mb_strtolower(trim((string) $post->getContent() . ' ' . (string) $post->getEventTitle() . ' ' . (string) $post->getEventLocation()));
        foreach ($interestTokens as $token) {
            if ($token !== '' && mb_strlen($token) >= 3 && str_contains($text, $token)) {
                $score += 8;
            }
        }

        if ($analysis instanceof FeedAiAnalysis) {
            if ($analysis->getAutoAction() === 'block') {
                $score -= 120;
            } elseif ($analysis->getAutoAction() === 'review') {
                $score -= 50;
            }
            $score -= (int) round($analysis->getToxicityScore() * 0.35);
            $score -= (int) round($analysis->getSpamScore() * 0.40);
            $score -= (int) round($analysis->getHateSpeechScore() * 0.50);
        }

        return $score;
    }

    /**
     * @return string[]
     */
    private function buildUserInterestTokens(User $user): array
    {
        $tokens = [];
        $pseudo = trim((string) $user->getPseudo());
        if ($pseudo !== '') {
            $tokens[] = mb_strtolower($pseudo);
        }
        $tokens[] = mb_strtolower($user->getRole()->value);

        foreach ($user->getCandidatures() as $candidature) {
            $tokens[] = mb_strtolower((string) $candidature->getNiveau());
            $tokens[] = mb_strtolower((string) $candidature->getRegion());
            $tokens[] = mb_strtolower((string) $candidature->getPlayStyle());
            if ($candidature->getEquipe()) {
                $tokens[] = mb_strtolower((string) $candidature->getEquipe()->getNomEquipe());
                $tokens[] = mb_strtolower((string) $candidature->getEquipe()->getTag());
            }
        }

        $managedTeams = $this->equipeRepository->findBy(['manager' => $user]);
        foreach ($managedTeams as $team) {
            $tokens[] = mb_strtolower((string) $team->getNomEquipe());
            $tokens[] = mb_strtolower((string) $team->getTag());
            $tokens[] = mb_strtolower((string) $team->getRegion());
        }

        $clean = [];
        foreach ($tokens as $token) {
            $value = trim((string) $token);
            if ($value === '' || in_array($value, $this->stopWords, true)) {
                continue;
            }
            $clean[] = $value;
        }

        return array_values(array_unique($clean));
    }

    /**
     * @return array<string,mixed>|null
     */
    private function enrichAnalysisWithAi(string $text, string $category, array $hashtags, string $summaryShort, string $summaryLong): ?array
    {
        $ml = $this->feedAiPythonService->runTask('enrich_analysis', [
            'text' => $text,
            'category' => $category,
            'hashtags' => $hashtags,
            'summary_short' => $summaryShort,
            'summary_long' => $summaryLong,
        ]);
        if (is_array($ml)) {
            return $ml;
        }
        return null;
    }

}

