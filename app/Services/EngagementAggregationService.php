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
    ) {}

    /**
     * Per-bucket engagement time series over the filtered range for the stickiness
     * chart: the bucket-local active users (range-adaptive DAU/WAU/MAU) plus the
     * rolling-30d stickiness and monthly user churn as of each bucket end
     * (now-capped, so a running bucket is measured as of today). Stickiness and
     * churn are null when their denominator is 0. Uncached for now (a LATERAL
     * rolling window per bucket, like the MRR time series).
     *
     * @return array<int, array{date: string, date_clear: string, active_users: int, stickiness: float|null, user_churn: float|null}>
     */
    public function getEngagementTimeSeries(StatsFilterDto $filters): array
    {
        $filterConditions = $this->filterConditionsBuilder->build($filters);
        $filterConditions['nowCap'] = now()->format(StatsRangeType::DATE_FORMAT);

        $builder = new EngagementTimeSeriesSqlBuilder;
        $sql = strtr($builder->build(), $this->formatFilter($filterConditions));

        return array_map(function (array $row): array {
            $dau = (float) $row['dau'];
            $mau = (int) $row['mau'];
            $mauPrior = (int) $row['mau_prior'];
            $churned = (int) $row['churned'];

            return [
                'date' => $row['date'],
                'date_clear' => $row['date_clear'],
                'active_users' => (int) $row['active_users'],
                'stickiness' => $mau > 0 ? round($dau / $mau * 100, 1) : null,
                'user_churn' => $mauPrior > 0 ? round($churned / $mauPrior * 100, 1) : null,
            ];
        }, $this->getDataArray($sql));
    }
}
