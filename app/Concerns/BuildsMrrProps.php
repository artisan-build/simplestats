<?php

namespace App\Concerns;

use App\DTOs\StatsFilterDto;
use App\Enums\StatsRangeType;
use App\Models\Team;
use App\Services\StatsService;

trait BuildsMrrProps
{
    /**
     * @return array{mrr: float, arr: float, mrr_prev: float}|null
     */
    protected function buildMrrSnapshot(Team $teamStats, StatsFilterDto $filters): ?array
    {
        if (! $this->projectTracksSubscription($teamStats, $filters)) {
            return null;
        }

        return $this->statsDetailsService->getMrrSnapshot($filters);
    }

    /**
     * @return array<int, array{date: string, date_clear: string, mrr: float, subscribers: int}>|null
     */
    protected function buildMrrTimeSeries(Team $teamStats, StatsFilterDto $filters): ?array
    {
        if (! $this->projectTracksSubscription($teamStats, $filters)) {
            return null;
        }

        return $this->statsDetailsService->getMrrTimeSeries($filters);
    }

    /**
     * @return array<int, array{date: string, date_clear: string, mrr: float, subscribers: int}>|null
     */
    protected function buildMrrTimeSeriesComparison(Team $teamStats, StatsFilterDto $filters): ?array
    {
        if (! $filters->comparison || $filters->rangeType === StatsRangeType::YEAR) {
            return null;
        }

        return $this->buildMrrTimeSeries($teamStats, StatsService::createPrevFiltersForComparison($filters));
    }

    /**
     * @return array<int, array<string, mixed>>|null
     */
    protected function buildMrrMovements(Team $teamStats, StatsFilterDto $filters): ?array
    {
        if (! $this->projectTracksSubscription($teamStats, $filters)) {
            return null;
        }

        return $this->statsDetailsService->getMrrMovements($filters);
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function buildMrrRetention(Team $teamStats, StatsFilterDto $filters): ?array
    {
        if (! $this->projectTracksSubscription($teamStats, $filters)) {
            return null;
        }

        return $this->statsDetailsService->getMrrRetention($filters);
    }

    /**
     * @return array<int, array{date: string, date_clear: string, mrr: float, gross_churn_rate: float|null, net_churn_rate: float|null, quick_ratio: float|null}>|null
     */
    protected function buildRevenueChurn(Team $teamStats, StatsFilterDto $filters): ?array
    {
        if (! $this->projectTracksSubscription($teamStats, $filters)) {
            return null;
        }

        return $this->statsDetailsService->getRevenueChurn($filters);
    }

    /**
     * Per-plan MRR breakdown for the metric detail view. Gated like every MRR
     * surface; null when the project does not track subscriptions.
     *
     * @return array<int, array{plan: string|null, subscribers: int, mrr: float}>|null
     */
    protected function buildPlanBreakdown(Team $teamStats, StatsFilterDto $filters): ?array
    {
        if (! $this->projectTracksSubscription($teamStats, $filters)) {
            return null;
        }

        return $this->statsDetailsService->getMrrByPlan($filters);
    }

    /**
     * Gated on the explicit SUBSCRIPTION intent bit (not on whether a subscription
     * payment already arrived) so a fresh subscription project shows an empty MRR
     * line from day one instead of the chart appearing only after its first payment.
     */
    protected function projectTracksSubscription(Team $teamStats, StatsFilterDto $filters): bool
    {
        return $this->projectTracksBit($teamStats, $filters, StatsService::TRACKING_USAGE_BITS_SUBSCRIPTION);
    }
}
