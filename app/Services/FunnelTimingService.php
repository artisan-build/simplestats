<?php

namespace App\Services;

use App\Builder\BaseSqlBuilder;
use App\Builder\StatsFilterConditionsBuilder;
use App\Builder\TimeToConvertSqlBuilder;
use App\Builder\TimeToPaySqlBuilder;
use App\Concerns\RunsFilteredStatsQueries;
use App\DTOs\StatsFilterDto;

/**
 * Funnel-timing aggregation: how fast users move through the funnel, as median days.
 * Split out like the MRR/retention aggregations because a per-user time difference
 * reduced to a percentile does not fit the period (pd_*) / lifetime (lt_*) model.
 * Reuses the shared StatsFilterConditionsBuilder, so channel/property filters narrow
 * the medians identically to the rest of the stats.
 */
class FunnelTimingService
{
    use RunsFilteredStatsQueries;

    public function __construct(
        private StatsFilterConditionsBuilder $filterConditionsBuilder,
    ) {}

    /**
     * Median days from registration to first payment for customers whose first
     * payment falls in the range. Null when no customer qualifies.
     */
    public function getTimeToPay(StatsFilterDto $filters): ?float
    {
        $median = $this->getDataArray($this->buildSql(new TimeToPaySqlBuilder, $filters))[0]['median_days'] ?? null;

        return $median === null ? null : round((float) $median, 1);
    }

    /**
     * Per-bucket median days from first visit to registration (newest bucket first),
     * one row per registration bucket that has linked visitors.
     *
     * @return array<int, array{date: string, date_clear: string, time_to_convert: float}>
     */
    public function getTimeToConvertSeries(StatsFilterDto $filters): array
    {
        $rows = $this->getDataArray($this->buildSql(new TimeToConvertSqlBuilder, $filters));

        return array_map(fn (array $row): array => [
            'date' => $row['date'],
            'date_clear' => $row['date_clear'],
            'time_to_convert' => round((float) $row['time_to_convert'], 1),
        ], $rows);
    }

    private function buildSql(BaseSqlBuilder $builder, StatsFilterDto $filters): string
    {
        $filterConditions = $this->filterConditionsBuilder->build($filters);

        return strtr($builder->build(), $this->formatFilter($filterConditions));
    }
}
