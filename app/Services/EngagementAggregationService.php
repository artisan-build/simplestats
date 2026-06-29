<?php

namespace App\Services;

use App\Builder\EngagementTimeSeriesSqlBuilder;
use App\Builder\StatsFilterConditionsBuilder;
use App\Concerns\RunsFilteredStatsQueries;
use App\DTOs\StatsFilterDto;
use App\Enums\StatsRangeType;

/**
 * Trailing-window engagement metrics (active users, stickiness, user churn) for
 * the stickiness chart. Split out of StatsAggregationsService for the same reason
 * as the MRR and retention aggregations: these are point-in-time, trailing-window
 * figures that do not fit the per-bucket period (pd_*) / lifetime (lt_*) model.
 * Reuses the shared StatsFilterConditionsBuilder, so channel/property/event
 * filters narrow the counts identically to the period stats (channel-level
 * engagement).
 */
class EngagementAggregationService
{
    use RunsFilteredStatsQueries;

    public function __construct(
        private StatsFilterConditionsBuilder $filterConditionsBuilder,
        private StatsAggregationsService $aggregations,
    ) {}

    /**
     * Per-bucket engagement time series over the filtered range for the stickiness
     * chart: the bucket-local active users (range-adaptive DAU/WAU/MAU) plus the
     * rolling-30d stickiness and monthly user churn as of each bucket end
     * (now-capped, so a running bucket is measured as of today). Stickiness and
     * churn are null when their denominator is 0. Closed buckets are served from
     * the per-bucket cache, the open bucket is recomputed live (getCachedSeries).
     *
     * @return array<int, array{date: string, date_clear: string, stickiness: float|null, user_churn: float|null}>
     */
    public function getEngagementTimeSeries(StatsFilterDto $filters): array
    {
        $rows = $this->aggregations->getCachedSeries(
            $filters,
            'engagement',
            fn (StatsFilterDto $rangeFilters): array => $this->computeEngagementRaw($rangeFilters),
        );

        return array_map(function (array $row): array {
            $dau = (float) $row['dau'];
            $mau = (int) $row['mau'];
            $mauPrior = (int) $row['mau_prior'];
            $churned = (int) $row['churned'];

            return [
                'date' => $row['date'],
                'date_clear' => $row['date_clear'],
                'stickiness' => $mau > 0 ? round($dau / $mau * 100, 1) : null,
                'user_churn' => $mauPrior > 0 ? round($churned / $mauPrior * 100, 1) : null,
            ];
        }, $rows);
    }

    /**
     * Raw rolling-window inputs per bucket; only raw counts are cached, the
     * stickiness / user-churn ratios are derived in PHP over the merged
     * closed+open set.
     *
     * @return array<int, array<string, mixed>>
     */
    private function computeEngagementRaw(StatsFilterDto $filters): array
    {
        $filterConditions = $this->filterConditionsBuilder->build($filters);
        $filterConditions['nowCap'] = now()->format(StatsRangeType::DATE_FORMAT);

        $builder = new EngagementTimeSeriesSqlBuilder;
        $sql = strtr($builder->build(), $this->formatFilter($filterConditions));

        return $this->getDataArray($sql);
    }
}
