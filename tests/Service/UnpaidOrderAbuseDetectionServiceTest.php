<?php

namespace App\Tests\Service;

use App\Repository\CommandeRepository;
use App\Service\OrderAbuseMLService;
use App\Service\UnpaidOrderAbuseDetectionService;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;

class UnpaidOrderAbuseDetectionServiceTest extends TestCase
{
    private $commandeRepository;
    private $cachePool;
    private $orderAbuseMLService;
    private $service;

    protected function setUp(): void
    {
        $this->commandeRepository = $this->createMock(CommandeRepository::class);
        $this->cachePool = $this->createMock(CacheItemPoolInterface::class);
        $this->orderAbuseMLService = $this->createMock(OrderAbuseMLService::class);

        $this->service = new UnpaidOrderAbuseDetectionService(
            $this->commandeRepository,
            $this->cachePool,
            $this->orderAbuseMLService
        );
    }

    public function testAssessAndMaybeBlockReturnsBlockedWhenPendingOrdersTooHigh(): void
    {
        // Setup: User has 5 total orders, 4 are pending, 0 paid.
        $metrics = [
            'totalOrders' => 5,
            'pendingOrders' => 4,
            'paidOrders' => 0,
            'cancelledOrders' => 1,
            'draftOrders' => 0,
            'identityVariants' => 1
        ];

        $this->commandeRepository->expects($this->once())
            ->method('getBehaviorMetricsByPhone')
            ->willReturn($metrics);

        // No active block in cache or DB
        $this->cachePool->method('getItem')->willReturn($this->createMock(CacheItemInterface::class));
        $this->commandeRepository->method('findActiveAiBlockDecision')->willReturn(null);

        // ML prediction (optional but we mock it)
        $this->orderAbuseMLService->method('predictRiskScore')->willReturn(['risk_score' => 50.0]);

        $result = $this->service->assessAndMaybeBlock('Test', 'User', 12345678);

        $this->assertTrue($result['blocked']);
        $this->assertStringContainsString('temporairement bloquee', $result['message']);
    }

    public function testAssessAndMaybeBlockReturnsNotBlockedForGoodUser(): void
    {
        $metrics = [
            'totalOrders' => 10,
            'pendingOrders' => 0,
            'paidOrders' => 10,
            'cancelledOrders' => 0,
            'draftOrders' => 0,
            'identityVariants' => 1
        ];

        $this->commandeRepository->expects($this->once())
            ->method('getBehaviorMetricsByPhone')
            ->willReturn($metrics);

        $this->cachePool->method('getItem')->willReturn($this->createMock(CacheItemInterface::class));
        $this->commandeRepository->method('findActiveAiBlockDecision')->willReturn(null);
        $this->orderAbuseMLService->method('predictRiskScore')->willReturn(['risk_score' => 5.0]);

        $result = $this->service->assessAndMaybeBlock('Good', 'User', 87654321);

        $this->assertFalse($result['blocked']);
    }
}
