<?php

namespace App\Service;

use App\Repository\CommandeRepository;
use Psr\Cache\CacheItemPoolInterface;

class UnpaidOrderAbuseDetectionService
{
    private const SCORE_BLOCK_THRESHOLD = 70.0;
    private const CACHE_TTL_SECONDS = 60;

    public function __construct(
        private CommandeRepository $commandeRepository,
        private CacheItemPoolInterface $cachePool,
        private OrderAbuseMLService $orderAbuseMLService
    ) {
    }

    /**
     * @return array{blocked: bool, score: float, block_until: ?string, message: string}
     */
    public function assessAndMaybeBlock(string $nom, string $prenom, int $numtel, ?int $userId = null): array
    {
        $identityKey = $this->buildIdentityKey($nom, $prenom, $numtel);
        $active = $this->getActiveBlock($numtel, $userId, $identityKey);
        if ($active !== null) {
            return $active;
        }

        $metrics = $this->commandeRepository->getBehaviorMetricsByPhone($numtel, $userId, $identityKey);
        $score = $this->resolveRiskScore($metrics, $nom, $prenom, $userId);
        $totalOrders = max(1, (int) ($metrics['totalOrders'] ?? 0));
        $pendingOrders = max(0, (int) ($metrics['pendingOrders'] ?? 0));
        $cancelledOrders = max(0, (int) ($metrics['cancelledOrders'] ?? 0));
        $paidOrders = max(0, (int) ($metrics['paidOrders'] ?? 0));
        $draftOrders = max(0, (int) ($metrics['draftOrders'] ?? 0));
        $unpaidOrders = $pendingOrders + $cancelledOrders;
        $unpaidRatio = $unpaidOrders / $totalOrders;

        $mustBlock = $score >= self::SCORE_BLOCK_THRESHOLD
            || ($pendingOrders >= 4 && $paidOrders === 0)
            || ($pendingOrders >= 2 && $paidOrders === 0)
            || ($pendingOrders >= 3 && $unpaidRatio >= 0.80)
            || ($pendingOrders >= 2 && $unpaidRatio >= 0.85)
            || ($unpaidOrders >= 4 && $unpaidRatio >= 0.70)
            || ($draftOrders >= 5 && $paidOrders <= 1);

        if (!$mustBlock) {
            return [
                'blocked' => false,
                'score' => $score,
                'block_until' => null,
                'message' => '',
            ];
        }

        $ttlMinutes = $this->computeBlockDurationMinutes($score);
        $blockUntil = (new \DateTimeImmutable())->modify(sprintf('+%d minutes', $ttlMinutes));
        $blockUntilIso = $blockUntil->format(\DateTimeInterface::ATOM);
        $decision = [
            'score' => $score,
            'block_until' => $blockUntilIso,
            'message' => sprintf(
                'Commande temporairement bloquee (score de risque: %.0f/100). Reessayez apres %s.',
                $score,
                $blockUntil->format('d/m/Y H:i')
            ),
        ];
        $this->saveBlockInCache($decision, $numtel, $userId, $nom, $prenom, $identityKey);

        return [
            'blocked' => true,
            'score' => $score,
            'block_until' => $blockUntilIso,
            'message' => $decision['message'],
        ];
    }

    /**
     * @return array{blocked: bool, score: float, block_until: ?string, message: string}|null
     */
    public function getActiveBlock(
        ?int $numtel = null,
        ?int $userId = null,
        ?string $identityKey = null,
        ?string $nom = null,
        ?string $prenom = null
    ): ?array
    {
        foreach ($this->buildLockKeys($numtel, $userId, $nom, $prenom, $identityKey) as $cacheKey) {
            $decision = $this->extractActiveDecisionFromCache($cacheKey);
            if ($decision !== null) {
                return $decision;
            }
        }

        $dbDecision = $this->commandeRepository->findActiveAiBlockDecision($userId, $numtel, $nom, $prenom, $identityKey);
        if ($dbDecision !== null) {
            $this->saveBlockInCache($dbDecision, $numtel, $userId, $nom, $prenom, $identityKey);
            return [
                'blocked' => true,
                'score' => (float) ($dbDecision['score'] ?? 0.0),
                'block_until' => (string) ($dbDecision['block_until'] ?? ''),
                'message' => $this->buildMessage((float) ($dbDecision['score'] ?? 0.0), (string) ($dbDecision['block_until'] ?? '')),
            ];
        }

        return null;
    }

    /**
     * @return array{blocked: bool, score: float, block_until: ?string, message: string}|null
     */
    public function getActiveBlockForUser(?int $userId = null): ?array
    {
        if ($userId === null) {
            return null;
        }

        return $this->getActiveBlock(null, $userId);
    }

    /**
     * @return array{blocked: bool, score: float, block_until: ?string, message: string}|null
     */
    private function extractActiveDecisionFromCache(string $cacheKey): ?array
    {
        $item = $this->cachePool->getItem($cacheKey);
        if (!$item->isHit()) {
            return null;
        }

        $payload = $item->get();
        if (!is_array($payload) || !isset($payload['block_until'])) {
            $this->cachePool->deleteItem($cacheKey);
            return null;
        }

        try {
            $until = new \DateTimeImmutable((string) $payload['block_until']);
        } catch (\Throwable) {
            $this->cachePool->deleteItem($cacheKey);
            return null;
        }

        if ($until <= new \DateTimeImmutable()) {
            $this->cachePool->deleteItem($cacheKey);
            return null;
        }

        $score = (float) ($payload['score'] ?? 0.0);

        return [
            'blocked' => true,
            'score' => $score,
            'block_until' => $until->format(\DateTimeInterface::ATOM),
            'message' => $this->buildMessage($score, $until->format(\DateTimeInterface::ATOM)),
        ];
    }

    /**
     * @param array<string,int> $metrics
     */
    private function computeScore(array $metrics, string $nom, string $prenom): float
    {
        $total = max(1, (int) ($metrics['totalOrders'] ?? 0));
        $pending = max(0, (int) ($metrics['pendingOrders'] ?? 0));
        $paid = max(0, (int) ($metrics['paidOrders'] ?? 0));
        $cancelled = max(0, (int) ($metrics['cancelledOrders'] ?? 0));
        $variants = max(0, (int) ($metrics['identityVariants'] ?? 0));

        $unpaidRatio = min(1.0, ($pending + $cancelled) / $total);
        $pendingNorm = min(1.0, $pending / 5.0);
        $cancelNorm = min(1.0, $cancelled / 4.0);
        $variantNorm = min(1.0, max(0, $variants - 1) / 3.0);
        $noPaidFlag = $paid === 0 ? 1.0 : 0.0;
        $nameQuality = (strlen(trim($nom)) < 2 || strlen(trim($prenom)) < 2) ? 1.0 : 0.0;

        // Logistic scoring model (lightweight behavioral ML style).
        $z = -1.3
            + (2.6 * $pendingNorm)
            + (2.0 * $unpaidRatio)
            + (1.0 * $cancelNorm)
            + (0.9 * $variantNorm)
            + (1.0 * $noPaidFlag)
            + (0.5 * $nameQuality);

        return round((1.0 / (1.0 + exp(-$z))) * 100.0, 2);
    }

    private function computeBlockDurationMinutes(float $score): int
    {
        $base = 30;
        $extra = (int) max(0, floor($score - self::SCORE_BLOCK_THRESHOLD));
        return min(180, $base + $extra * 2);
    }

    /**
     * @return list<string>
     */
    private function buildLockKeys(
        ?int $numtel = null,
        ?int $userId = null,
        ?string $nom = null,
        ?string $prenom = null,
        ?string $identityKey = null
    ): array
    {
        $keys = [];
        if ($userId !== null) {
            $keys[] = 'order_abuse_lock_user_' . $userId;
        }
        if ($numtel !== null && $numtel > 0) {
            $keys[] = 'order_abuse_lock_phone_' . $numtel;
        }

        [$resolvedNom, $resolvedPrenom] = $this->resolveNamePair($nom, $prenom, $identityKey);
        if ($resolvedNom !== '' && $resolvedPrenom !== '') {
            $keys[] = 'order_abuse_lock_name_' . substr(sha1($resolvedNom . '|' . $resolvedPrenom), 0, 20);
        }

        $normalizedIdentityKey = trim((string) ($identityKey ?? ''));
        if ($normalizedIdentityKey !== '') {
            $keys[] = 'order_abuse_lock_identity_' . substr(sha1($normalizedIdentityKey), 0, 20);
        }

        return array_values(array_unique($keys));
    }

    /**
     * @param array{score?: float, block_until?: string, message?: string} $decision
     */
    private function saveBlockInCache(
        array $decision,
        ?int $numtel = null,
        ?int $userId = null,
        ?string $nom = null,
        ?string $prenom = null,
        ?string $identityKey = null
    ): void {
        $blockUntil = (string) ($decision['block_until'] ?? '');
        if ($blockUntil === '') {
            return;
        }

        try {
            $until = new \DateTimeImmutable($blockUntil);
        } catch (\Throwable) {
            return;
        }

        $ttl = max(self::CACHE_TTL_SECONDS, $until->getTimestamp() - time());
        $payload = [
            'score' => (float) ($decision['score'] ?? 0.0),
            'block_until' => $until->format(\DateTimeInterface::ATOM),
            'message' => (string) ($decision['message'] ?? ''),
        ];

        foreach ($this->buildLockKeys($numtel, $userId, $nom, $prenom, $identityKey) as $cacheKey) {
            $item = $this->cachePool->getItem($cacheKey);
            $item->set($payload);
            $item->expiresAfter($ttl);
            $this->cachePool->save($item);
        }
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function resolveNamePair(?string $nom = null, ?string $prenom = null, ?string $identityKey = null): array
    {
        $normalizedNom = $this->normalizeIdentityPart((string) ($nom ?? ''));
        $normalizedPrenom = $this->normalizeIdentityPart((string) ($prenom ?? ''));
        if ($normalizedNom !== '' && $normalizedPrenom !== '') {
            return [$normalizedNom, $normalizedPrenom];
        }

        $key = trim((string) ($identityKey ?? ''));
        if ($key !== '') {
            $parts = explode('|', $key);
            if (count($parts) >= 2) {
                return [
                    $this->normalizeIdentityPart((string) $parts[0]),
                    $this->normalizeIdentityPart((string) $parts[1]),
                ];
            }
        }

        return ['', ''];
    }

    private function buildMessage(float $score, string $blockUntilIso): string
    {
        try {
            $until = new \DateTimeImmutable($blockUntilIso);
            $untilText = $until->format('d/m/Y H:i');
        } catch (\Throwable) {
            $untilText = 'plus tard';
        }

        return sprintf(
            'Commande temporairement bloquee (score de risque: %.0f/100). Reessayez apres %s.',
            $score,
            $untilText
        );
    }

    private function normalizeIdentityPart(string $value): string
    {
        $value = trim(mb_strtolower($value));
        return (string) preg_replace('/\s+/', ' ', $value);
    }

    /**
     * @param array<string,int> $metrics
     */
    private function resolveRiskScore(array $metrics, string $nom, string $prenom, ?int $userId = null): float
    {
        $heuristic = $this->computeScore($metrics, $nom, $prenom);
        $features = [
            'user_id' => $userId ?? 0,
            'has_user_account' => $userId !== null ? 1 : 0,
            'total_orders' => (int) ($metrics['totalOrders'] ?? 0),
            'pending_orders' => (int) ($metrics['pendingOrders'] ?? 0),
            'paid_orders' => (int) ($metrics['paidOrders'] ?? 0),
            'cancelled_orders' => (int) ($metrics['cancelledOrders'] ?? 0),
            'draft_orders' => (int) ($metrics['draftOrders'] ?? 0),
            'identity_variants' => (int) ($metrics['identityVariants'] ?? 0),
            'unpaid_ratio' => 0.0,
        ];
        $total = max(1, (int) $features['total_orders']);
        $features['unpaid_ratio'] = round(
            (((int) $features['pending_orders']) + ((int) $features['cancelled_orders'])) / $total,
            6
        );

        $prediction = $this->orderAbuseMLService->predictRiskScore($features);
        if ($prediction !== null && isset($prediction['risk_score'])) {
            return max((float) $prediction['risk_score'], $heuristic);
        }

        return $heuristic;
    }

    public function buildIdentityKey(string $nom, string $prenom, int $numtel): string
    {
        return sprintf(
            '%s|%s|%d',
            $this->normalizeIdentityPart($nom),
            $this->normalizeIdentityPart($prenom),
            $numtel
        );
    }
}
