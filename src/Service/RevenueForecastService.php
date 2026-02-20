<?php

namespace App\Service;

use App\Repository\CommandeRepository;
use App\Repository\PaymentRepository;

class RevenueForecastService
{
    public function __construct(
        private PaymentRepository $paymentRepository,
        private CommandeRepository $commandeRepository,
        private PaymentForecastMLService $paymentForecastMLService
    ) {
    }

    /**
     * @return array<string,mixed>
     */
    public function buildPaymentForecastDashboard(): array
    {
        $now = new \DateTimeImmutable('now');
        $historyStart = $now->modify('-120 days')->setTime(0, 0, 0);
        $events = $this->paymentRepository->getEventsSince($historyStart);

        $dailyRevenue = [];
        $dailyOrders = [];
        $dailyAttempts = [];
        $dailyFailed = [];
        $hourlyRevenue = array_fill(0, 24, 0.0);
        $hourlyOrders = array_fill(0, 24, 0);
        $weeklyRevenue = [];
        $weeklyOrders = [];

        foreach ($events as $event) {
            $createdAt = $event['createdAt'];
            $amount = (float) ($event['amount'] ?? 0.0);
            $status = (string) ($event['status'] ?? '');
            $isPaid = $this->isPaidStatus($status);
            $isFailed = $this->isFailedStatus($status);
            $isAttempt = $isPaid || $isFailed || str_contains($status, 'pending');
            $dayKey = $createdAt->format('Y-m-d');
            $weekKey = $createdAt->format('o-\WW');
            $hour = (int) $createdAt->format('G');

            if ($isPaid) {
                $dailyRevenue[$dayKey] = ($dailyRevenue[$dayKey] ?? 0.0) + $amount;
                $dailyOrders[$dayKey] = ($dailyOrders[$dayKey] ?? 0) + 1;

                $hourlyRevenue[$hour] += $amount;
                $hourlyOrders[$hour]++;

                $weeklyRevenue[$weekKey] = ($weeklyRevenue[$weekKey] ?? 0.0) + $amount;
                $weeklyOrders[$weekKey] = ($weeklyOrders[$weekKey] ?? 0) + 1;
            }

            if ($isAttempt) {
                $dailyAttempts[$dayKey] = ($dailyAttempts[$dayKey] ?? 0) + 1;
            }
            if ($isFailed) {
                $dailyFailed[$dayKey] = ($dailyFailed[$dayKey] ?? 0) + 1;
            }
        }

        $last30Revenue = $this->buildDailyWindow($dailyRevenue, $now, 30);
        $last30Orders = $this->buildDailyWindow($dailyOrders, $now, 30);
        $last30FailureRate = $this->buildFailureRateWindow($dailyAttempts, $dailyFailed, $now, 30);
        $last7Revenue = array_slice($last30Revenue, -7, 7, true);
        $prev7Revenue = array_slice($last30Revenue, -14, 7, true);
        $last7Orders = array_slice($last30Orders, -7, 7, true);
        $prev7Orders = array_slice($last30Orders, -14, 7, true);

        $dailyAvgRevenue = $this->averageNumericValues($last30Revenue);
        $dailyAvgOrders = $this->averageNumericValues($last30Orders);
        $revenueTrend = $this->computeTrendRatio($last7Revenue, $prev7Revenue);
        $ordersTrend = $this->computeTrendRatio($last7Orders, $prev7Orders);

        $todayKey = $now->format('Y-m-d');
        $todayRevenue = round((float) ($dailyRevenue[$todayKey] ?? 0.0), 2);
        $todayOrders = (int) ($dailyOrders[$todayKey] ?? 0);
        $sum7Revenue = round(array_sum($last7Revenue), 2);
        $sum30Revenue = round(array_sum($last30Revenue), 2);
        $sum7Orders = (int) round(array_sum($last7Orders));
        $sum30Orders = (int) round(array_sum($last30Orders));

        $statusCounts = $this->commandeRepository->countByStatut();
        $paidOrdersFromStatus = (int) ($statusCounts['paid'] ?? 0);
        $failedOrdersFromStatus = (int) ($statusCounts['cancelled'] ?? 0);
        $pendingOrdersFromStatus = (int) ($statusCounts['pending_payment'] ?? 0);
        $attemptedOrders = max(1, $paidOrdersFromStatus + $failedOrdersFromStatus + $pendingOrdersFromStatus);
        $failureRate = round(($failedOrdersFromStatus / $attemptedOrders) * 100, 2);
        $pendingRate = round(($pendingOrdersFromStatus / $attemptedOrders) * 100, 2);
        $successRate = round(($paidOrdersFromStatus / $attemptedOrders) * 100, 2);

        $baselineRevenueDay = round($dailyAvgRevenue * $revenueTrend, 2);
        $baselineOrdersDay = (int) round($dailyAvgOrders * $ordersTrend);
        $baselineFailureDay = round((float) end($last30FailureRate), 2);
        if ($baselineFailureDay <= 0.0) {
            $baselineFailureDay = $failureRate;
        }

        $forecastSource = 'heuristic_baseline';
        $mlForecast = $this->paymentForecastMLService->predictDailyForecast(
            $this->buildMlFeatures($last30Revenue, $last30Orders, $last30FailureRate, $now)
        );

        if ($mlForecast !== null) {
            $forecastRevenueDay = round((float) $mlForecast['forecast_revenue_day'], 2);
            $forecastOrdersDay = max(0, (int) $mlForecast['forecast_orders_day']);
            $forecastFailureDay = round((float) $mlForecast['forecast_failure_rate_day'], 2);
            $forecastSource = (string) ($mlForecast['source'] ?? 'ml_forecast_model');
        } else {
            $forecastRevenueDay = $baselineRevenueDay;
            $forecastOrdersDay = $baselineOrdersDay;
            $forecastFailureDay = $baselineFailureDay;
        }

        $forecastRevenueWeek = round($forecastRevenueDay * 7, 2);
        $forecastRevenueMonth = round($forecastRevenueDay * 30, 2);
        $forecastOrdersWeek = (int) round($forecastOrdersDay * 7);
        $forecastOrdersMonth = (int) round($forecastOrdersDay * 30);

        return [
            'generatedAt' => $now,
            'model' => [
                'source' => $forecastSource,
                'trained' => $this->paymentForecastMLService->hasTrainedModel(),
            ],
            'revenue' => [
                'today' => $todayRevenue,
                'last7' => $sum7Revenue,
                'last30' => $sum30Revenue,
                'forecast_day' => $forecastRevenueDay,
                'forecast_week' => $forecastRevenueWeek,
                'forecast_month' => $forecastRevenueMonth,
                'trend_percent' => round(($revenueTrend - 1.0) * 100, 2),
            ],
            'paid_orders' => [
                'today' => $todayOrders,
                'last7' => $sum7Orders,
                'last30' => $sum30Orders,
                'forecast_day' => $forecastOrdersDay,
                'forecast_week' => $forecastOrdersWeek,
                'forecast_month' => $forecastOrdersMonth,
                'trend_percent' => round(($ordersTrend - 1.0) * 100, 2),
                'from_status_paid' => $paidOrdersFromStatus,
            ],
            'failure' => [
                'failure_rate' => $failureRate,
                'pending_rate' => $pendingRate,
                'success_rate' => $successRate,
                'forecast_day' => $forecastFailureDay,
                'failed_orders' => $failedOrdersFromStatus,
                'pending_orders' => $pendingOrdersFromStatus,
                'paid_orders' => $paidOrdersFromStatus,
                'attempted_orders' => $attemptedOrders,
            ],
            'series' => [
                'hourly' => $this->buildHourlySeries($hourlyRevenue, $hourlyOrders),
                'daily' => $this->buildDailySeries($dailyRevenue, $dailyOrders, $now, 14),
                'weekly' => $this->buildWeeklySeries($weeklyRevenue, $weeklyOrders, $now, 8),
            ],
        ];
    }

    /**
     * @param array<string,float|int> $bucket
     * @return array<string,float|int>
     */
    private function buildDailyWindow(array $bucket, \DateTimeImmutable $now, int $days): array
    {
        $window = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $key = $now->modify('-' . $i . ' days')->format('Y-m-d');
            $window[$key] = (float) ($bucket[$key] ?? 0.0);
        }

        return $window;
    }

    /**
     * @param array<string,float|int> $series
     */
    private function averageNumericValues(array $series): float
    {
        if ($series === []) {
            return 0.0;
        }

        return (float) array_sum($series) / max(1, count($series));
    }

    /**
     * @param array<string,int> $attempts
     * @param array<string,int> $failed
     * @return array<string,float>
     */
    private function buildFailureRateWindow(array $attempts, array $failed, \DateTimeImmutable $now, int $days): array
    {
        $window = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $key = $now->modify('-' . $i . ' days')->format('Y-m-d');
            $dayAttempts = (int) ($attempts[$key] ?? 0);
            $dayFailed = (int) ($failed[$key] ?? 0);
            $window[$key] = $dayAttempts > 0 ? round(($dayFailed / $dayAttempts) * 100.0, 6) : 0.0;
        }
        return $window;
    }

    /**
     * @param array<string,float|int> $last30Revenue
     * @param array<string,float|int> $last30Orders
     * @param array<string,float> $last30FailureRate
     * @return array<string,int|float>
     */
    private function buildMlFeatures(
        array $last30Revenue,
        array $last30Orders,
        array $last30FailureRate,
        \DateTimeImmutable $now
    ): array {
        return [
            'rev_1d' => (float) array_sum(array_slice($last30Revenue, -1, 1, true)),
            'rev_3d' => (float) array_sum(array_slice($last30Revenue, -3, 3, true)),
            'rev_7d' => (float) array_sum(array_slice($last30Revenue, -7, 7, true)),
            'rev_14d' => (float) array_sum(array_slice($last30Revenue, -14, 14, true)),
            'rev_30d' => (float) array_sum($last30Revenue),
            'orders_1d' => (float) array_sum(array_slice($last30Orders, -1, 1, true)),
            'orders_3d' => (float) array_sum(array_slice($last30Orders, -3, 3, true)),
            'orders_7d' => (float) array_sum(array_slice($last30Orders, -7, 7, true)),
            'orders_14d' => (float) array_sum(array_slice($last30Orders, -14, 14, true)),
            'orders_30d' => (float) array_sum($last30Orders),
            'fail_1d' => (float) array_sum(array_slice($last30FailureRate, -1, 1, true)),
            'fail_7d' => $this->averageNumericValues(array_slice($last30FailureRate, -7, 7, true)),
            'fail_30d' => $this->averageNumericValues($last30FailureRate),
            'dow' => (int) $now->format('N'),
            'is_weekend' => (int) ((int) $now->format('N') >= 6),
        ];
    }

    private function isPaidStatus(string $status): bool
    {
        return str_contains($status, 'paid')
            || str_contains($status, 'success')
            || str_contains($status, 'succeeded');
    }

    private function isFailedStatus(string $status): bool
    {
        return str_contains($status, 'failed') || str_contains($status, 'cancel');
    }

    /**
     * @param array<string,float|int> $latest
     * @param array<string,float|int> $previous
     */
    private function computeTrendRatio(array $latest, array $previous): float
    {
        $latestSum = (float) array_sum($latest);
        $previousSum = (float) array_sum($previous);

        if ($previousSum <= 0.0) {
            if ($latestSum <= 0.0) {
                return 1.0;
            }

            return 1.15;
        }

        $ratio = $latestSum / $previousSum;
        return max(0.6, min(1.8, $ratio));
    }

    /**
     * @param array<int,float> $revenueByHour
     * @param array<int,int> $ordersByHour
     * @return list<array{label:string,revenue:float,orders:int,revenue_pct:float,orders_pct:float}>
     */
    private function buildHourlySeries(array $revenueByHour, array $ordersByHour): array
    {
        $maxRevenue = max(1.0, (float) max($revenueByHour));
        $maxOrders = max(1, (int) max($ordersByHour));
        $rows = [];

        for ($hour = 0; $hour < 24; $hour++) {
            $revenue = round((float) ($revenueByHour[$hour] ?? 0.0), 2);
            $orders = (int) ($ordersByHour[$hour] ?? 0);
            $rows[] = [
                'label' => sprintf('%02dh', $hour),
                'revenue' => $revenue,
                'orders' => $orders,
                'revenue_pct' => round(($revenue / $maxRevenue) * 100, 2),
                'orders_pct' => round(($orders / $maxOrders) * 100, 2),
            ];
        }

        return $rows;
    }

    /**
     * @param array<string,float> $dailyRevenue
     * @param array<string,int> $dailyOrders
     * @return list<array{label:string,revenue:float,orders:int,revenue_pct:float,orders_pct:float}>
     */
    private function buildDailySeries(
        array $dailyRevenue,
        array $dailyOrders,
        \DateTimeImmutable $now,
        int $days
    ): array {
        $entries = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $day = $now->modify('-' . $i . ' days');
            $key = $day->format('Y-m-d');
            $entries[] = [
                'label' => $day->format('d/m'),
                'revenue' => round((float) ($dailyRevenue[$key] ?? 0.0), 2),
                'orders' => (int) ($dailyOrders[$key] ?? 0),
            ];
        }

        $maxRevenue = max(1.0, ...array_map(static fn (array $row): float => (float) $row['revenue'], $entries));
        $maxOrders = max(1, ...array_map(static fn (array $row): int => (int) $row['orders'], $entries));

        foreach ($entries as &$row) {
            $row['revenue_pct'] = round((((float) $row['revenue']) / $maxRevenue) * 100, 2);
            $row['orders_pct'] = round((((int) $row['orders']) / $maxOrders) * 100, 2);
        }
        unset($row);

        return $entries;
    }

    /**
     * @param array<string,float> $weeklyRevenue
     * @param array<string,int> $weeklyOrders
     * @return list<array{label:string,revenue:float,orders:int,revenue_pct:float,orders_pct:float}>
     */
    private function buildWeeklySeries(
        array $weeklyRevenue,
        array $weeklyOrders,
        \DateTimeImmutable $now,
        int $weeks
    ): array {
        $entries = [];
        for ($i = $weeks - 1; $i >= 0; $i--) {
            $week = $now->modify('-' . $i . ' weeks');
            $weekKey = $week->format('o-\WW');
            $entries[] = [
                'label' => 'S' . $week->format('W') . ' ' . $week->format('Y'),
                'revenue' => round((float) ($weeklyRevenue[$weekKey] ?? 0.0), 2),
                'orders' => (int) ($weeklyOrders[$weekKey] ?? 0),
            ];
        }

        $maxRevenue = max(1.0, ...array_map(static fn (array $row): float => (float) $row['revenue'], $entries));
        $maxOrders = max(1, ...array_map(static fn (array $row): int => (int) $row['orders'], $entries));

        foreach ($entries as &$row) {
            $row['revenue_pct'] = round((((float) $row['revenue']) / $maxRevenue) * 100, 2);
            $row['orders_pct'] = round((((int) $row['orders']) / $maxOrders) * 100, 2);
        }
        unset($row);

        return $entries;
    }

}
