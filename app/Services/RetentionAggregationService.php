<?php

namespace App\Services;

use App\Builder\RetentionCohortSqlBuilder;
use App\Builder\StatsFilterConditionsBuilder;
use App\Concerns\RunsFilteredStatsQueries;
use App\DTOs\StatsFilterDto;

/**
 * Registration-cohort retention. Split out of StatsAggregationsService for the
 * same reason as the MRR aggregation: a cohort matrix (cohort bucket x age period)
 * does not fit the per-bucket period (pd_*) / lifetime (lt_*) model. Reuses the
 * shared StatsFilterConditionsBuilder, so channel/property/event filters narrow
 * the cohorts identically to the period stats (channel-level retention).
 */
class RetentionAggregationService
{
    use RunsFilteredStatsQueries;

    public function __construct(
        private StatsFilterConditionsBuilder $filterConditionsBuilder,
    ) {}

    /**
     * Retention matrix over the filtered range: one row per registration cohort
     * (oldest first), each holding the cohort size and a retention array indexed
     * by age period. Period 0 is the 100 % baseline; period n (n >= 1) is the
     * share of the cohort with a return login in the n-th following bucket.
     * Not-yet-observable periods are null (vs 0.0 for an observed empty period),
     * which produces the triangular cohort grid. Every bucket in the range is a row
     * (oldest first), so the cohort axis matches the selected range exactly; a bucket
     * without signups is a 0-user row with all cells null. Only when no bucket has a
     * single registration is the result empty (the view shows its empty state then).
     *
     * bucket_labels holds the absolute label of every bucket (index 0..maxIdx), so the
     * view can resolve a relative period of a cohort to its real calendar bucket
     * (cohort at idx, period p => bucket_labels[idx + p]) for the per-cell tooltip.
     *
     * @return array{period_count: int, bucket_labels: array<int, string>, cohorts: array<int, array{cohort: string, idx: int, size: int, retention: array<int, float|null>}>}
     */
    public function getRetentionCohorts(StatsFilterDto $filters): array
    {
        $filterConditions = $this->filterConditionsBuilder->build($filters);

        $builder = new RetentionCohortSqlBuilder;
        $sql = strtr($builder->build(), $this->formatFilter($filterConditions));

        return $this->pivotCohorts($this->getDataArray($sql), $filters->rangeAmount);
    }

    /**
     * @param  array<int, array{cohort_idx: int, cohort_label: string, cohort_size: int, period_offset: ?int, retained: ?int}>  $rows
     * @return array{period_count: int, bucket_labels: array<int, string>, cohorts: array<int, array{cohort: string, idx: int, size: int, retention: array<int, float|null>}>}
     */
    private function pivotCohorts(array $rows, int $rangeAmount): array
    {
        $maxIdx = $rangeAmount - 1;
        $periodCount = $rangeAmount;

        $byCohort = [];

        foreach ($rows as $row) {
            $idx = (int) $row['cohort_idx'];

            if (! isset($byCohort[$idx])) {
                $byCohort[$idx] = [
                    'cohort' => $row['cohort_label'],
                    'size' => (int) $row['cohort_size'],
                    'periods' => [],
                ];
            }

            if ($row['period_offset'] !== null) {
                $byCohort[$idx]['periods'][(int) $row['period_offset']] = (int) $row['retained'];
            }
        }

        $hasRegistrations = collect($byCohort)->contains(fn ($cohort) => $cohort['size'] > 0);

        if (! $hasRegistrations) {
            return [
                'period_count' => $periodCount,
                'bucket_labels' => [],
                'cohorts' => [],
            ];
        }

        $bucketLabels = [];
        for ($idx = 0; $idx <= $maxIdx; $idx++) {
            $bucketLabels[$idx] = $byCohort[$idx]['cohort'] ?? null;
        }

        $cohorts = [];

        for ($idx = 0; $idx <= $maxIdx; $idx++) {
            if (! isset($byCohort[$idx])) {
                continue;
            }

            $cohort = $byCohort[$idx];
            $size = $cohort['size'];
            $observableMax = $maxIdx - $idx;

            $retention = [];

            for ($period = 0; $period < $periodCount; $period++) {
                if ($period > $observableMax || $size === 0) {
                    $retention[] = null;
                } elseif ($period === 0) {
                    $retention[] = 100.0;
                } else {
                    $retention[] = round(($cohort['periods'][$period] ?? 0) / $size * 100, 1);
                }
            }

            $cohorts[] = [
                'cohort' => $cohort['cohort'],
                'idx' => $idx,
                'size' => $size,
                'retention' => $retention,
            ];
        }

        return [
            'period_count' => $periodCount,
            'bucket_labels' => $bucketLabels,
            'cohorts' => $cohorts,
        ];
    }
}
