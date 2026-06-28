<?php

namespace App\Services;

use App\Builder\MrrByPlanSqlBuilder;
use App\Builder\MrrPerUserBucketSqlBuilder;
use App\Builder\MrrSnapshotSqlBuilder;
use App\Builder\MrrTimeSeriesSqlBuilder;
use App\Builder\StatsFilterConditionsBuilder;
use App\Concerns\RunsFilteredStatsQueries;
use App\DTOs\StatsFilterDto;
use App\Enums\StatsRangeType;
use Carbon\CarbonImmutable;
use Illuminate\Support\Arr;

/**
 * Recurring-revenue aggregation: MRR/ARR snapshots (and, in later phases, the
 * per-bucket MRR time series and movements). Split out of
 * StatsAggregationsService because MRR is a non-monotonic point-in-time snapshot
 * that does not fit the period (pd_*) / lifetime (lt_*) bucket model. Reuses the
 * shared StatsFilterConditionsBuilder, so channel/property filters narrow MRR
 * identically to the period stats.
 */
class MrrAggregationService
{
    use RunsFilteredStatsQueries;

    public function __construct(
        private StatsFilterConditionsBuilder $filterConditionsBuilder,
    ) {}

    /**
     * Active recurring revenue as a point-in-time snapshot, not a period sum.
     * MRR is taken at the range end (current) and the range start (comparison),
     * so the headline tile can show growth across the visible range. ARR = MRR
     * x 12.
     *
     * @return array{mrr: float, arr: float, mrr_prev: float}
     */
    public function getMrrSnapshot(StatsFilterDto $filters): array
    {
        $filterConditions = $this->filterConditionsBuilder->build($filters);

        // The running bucket ends in the future (e.g. end of month), which would
        // push the churn window forward and drop still-active subs. Cap the
        // current snapshot at now so it is taken as-of today regardless of range.
        $rangeEnd = min($filterConditions['endDateEnd'], $this->nowCap());
        $rangeStart = $filterConditions['startDateStart'];

        $current = $this->computeMrrAtDate($filterConditions, $rangeEnd);
        $previous = $this->computeMrrAtDate($filterConditions, $rangeStart);

        return [
            'mrr' => $current,
            'arr' => round($current * 12, 2),
            'mrr_prev' => $previous,
        ];
    }

    /**
     * Per-bucket MRR snapshot line over the filtered range: one active-recurring
     * snapshot per bucket, taken at the bucket end. Unlike getMrrSnapshot (two
     * points), this is the full curve for the MRR chart. Uncached for now (a
     * lateral snapshot per bucket); the per-bucket shape does fit the
     * StatsAggregation bucket cache, which is the planned follow-up.
     *
     * @return array<int, array{date: string, date_clear: string, mrr: float, subscribers: int}>
     */
    public function getMrrTimeSeries(StatsFilterDto $filters): array
    {
        $filterConditions = $this->filterConditionsBuilder->build($filters);

        $builder = new MrrTimeSeriesSqlBuilder;
        $sql = strtr($builder->build(), $this->formatFilter($this->withNowCap($filterConditions)));

        return array_map(function (array $row): array {
            $row['mrr'] = round((float) $row['mrr'], 2);
            $row['subscribers'] = (int) $row['subscribers'];

            return $row;
        }, $this->getDataArray($sql));
    }

    /**
     * Per-bucket MRR movement breakdown (New / Reactivation / Expansion /
     * Contraction / Churn) plus starting/ending MRR, derived from the per-user
     * MRR matrix by classifying each user's change between consecutive buckets.
     *
     * Reactivation (vs New) is detected within the computed window only (range
     * plus one leading bucket): a 0 -> x user who was active in an earlier bucket
     * here counts as reactivated, otherwise as new. A user whose prior active
     * period lies entirely before the visible range is counted as New.
     *
     * @return array<int, array{date: string, date_clear: string, starting_mrr: float, new: float, reactivation: float, expansion: float, contraction: float, churn: float, ending_mrr: float, net_change: float}>
     */
    public function getMrrMovements(StatsFilterDto $filters): array
    {
        $filterConditions = $this->filterConditionsBuilder->build($filters);

        $builder = new MrrPerUserBucketSqlBuilder;
        $sql = strtr($builder->build(), $this->formatFilter($this->withNowCap($filterConditions)));

        [$buckets, $userMrrByBucket] = $this->pivotPerUserBuckets($this->getDataArray($sql));

        $everActive = array_fill_keys(array_keys($userMrrByBucket[0] ?? []), true);

        $movements = [];

        for ($i = 1; $i < count($buckets); $i++) {
            $prev = $userMrrByBucket[$i - 1];
            $curr = $userMrrByBucket[$i];

            $new = $reactivation = $expansion = $contraction = $churn = 0.0;

            foreach (array_keys($prev + $curr) as $userId) {
                $p = $prev[$userId] ?? 0.0;
                $c = $curr[$userId] ?? 0.0;

                if ($p == 0.0 && $c > 0.0) {
                    if (! empty($everActive[$userId])) {
                        $reactivation += $c;
                    } else {
                        $new += $c;
                    }
                } elseif ($p > 0.0 && $c > $p) {
                    $expansion += $c - $p;
                } elseif ($p > 0.0 && $c > 0.0 && $c < $p) {
                    $contraction += $p - $c;
                } elseif ($p > 0.0 && $c == 0.0) {
                    $churn += $p;
                }
            }

            $starting = array_sum($prev);
            $ending = array_sum($curr);

            $movements[] = [
                'date' => $buckets[$i]['date'],
                'date_clear' => $buckets[$i]['date_clear'],
                'starting_mrr' => round($starting, 2),
                'new' => round($new, 2),
                'reactivation' => round($reactivation, 2),
                'expansion' => round($expansion, 2),
                'contraction' => round($contraction, 2),
                'churn' => round($churn, 2),
                'ending_mrr' => round($ending, 2),
                'net_change' => round($ending - $starting, 2),
            ];

            foreach (array_keys($curr) as $userId) {
                $everActive[$userId] = true;
            }
        }

        return array_reverse($movements);
    }

    /**
     * NRR / GRR / Quick Ratio over the filtered range, derived from the movement
     * totals against the range-start MRR. Ratios are null when their denominator
     * is zero (no starting MRR, or no contraction/churn for the Quick Ratio).
     *
     * @return array{nrr: float|null, grr: float|null, quick_ratio: float|null, starting_mrr: float, ending_mrr: float, new: float, reactivation: float, expansion: float, contraction: float, churn: float}
     */
    public function getMrrRetention(StatsFilterDto $filters): array
    {
        $movements = $this->getMrrMovements($filters);

        $oldest = Arr::last($movements);
        $newest = Arr::first($movements);

        $starting = $oldest['starting_mrr'] ?? 0.0;
        $ending = $newest['ending_mrr'] ?? 0.0;

        $new = array_sum(array_column($movements, 'new'));
        $reactivation = array_sum(array_column($movements, 'reactivation'));
        $expansion = array_sum(array_column($movements, 'expansion'));
        $contraction = array_sum(array_column($movements, 'contraction'));
        $churn = array_sum(array_column($movements, 'churn'));

        $churnAndContraction = $contraction + $churn;

        return [
            'nrr' => $starting > 0 ? round((($starting + $expansion - $contraction - $churn) / $starting) * 100, 1) : null,
            'grr' => $starting > 0 ? round((($starting - $contraction - $churn) / $starting) * 100, 1) : null,
            'quick_ratio' => $churnAndContraction > 0 ? round(($new + $expansion) / $churnAndContraction, 2) : null,
            'starting_mrr' => round($starting, 2),
            'ending_mrr' => round($ending, 2),
            'new' => round($new, 2),
            'reactivation' => round($reactivation, 2),
            'expansion' => round($expansion, 2),
            'contraction' => round($contraction, 2),
            'churn' => round($churn, 2),
        ];
    }

    /**
     * Per-bucket Gross / Net revenue churn rate, derived from the movement
     * breakdown (single source of truth, no extra query). Gross churn is the share
     * of the bucket's starting MRR lost to churn and contraction; Net churn offsets
     * it with expansion and reactivation and can go negative (net expansion). Rates
     * are null when the starting MRR is zero. The mrr field carries the bucket's
     * ending MRR as context for the chart bars.
     *
     * @return array<int, array{date: string, date_clear: string, mrr: float, gross_churn_rate: float|null, net_churn_rate: float|null, quick_ratio: float|null}>
     */
    public function getRevenueChurn(StatsFilterDto $filters): array
    {
        return array_map(function (array $bucket): array {
            $starting = $bucket['starting_mrr'];
            $grossLost = $bucket['churn'] + $bucket['contraction'];
            $netLost = $grossLost - $bucket['expansion'] - $bucket['reactivation'];

            return [
                'date' => $bucket['date'],
                'date_clear' => $bucket['date_clear'],
                'mrr' => $bucket['ending_mrr'],
                'gross_churn_rate' => $starting > 0 ? round($grossLost / $starting * 100, 1) : null,
                'net_churn_rate' => $starting > 0 ? round($netLost / $starting * 100, 1) : null,
                'quick_ratio' => $grossLost > 0 ? round(($bucket['new'] + $bucket['expansion']) / $grossLost, 2) : null,
            ];
        }, $this->getMrrMovements($filters));
    }

    /**
     * @param  array<int, array{date: string, date_clear: string, stats_user_id: ?string, mrr: ?float}>  $rows
     * @return array{0: array<int, array{date: string, date_clear: string}>, 1: array<int, array<string, float>>}
     */
    private function pivotPerUserBuckets(array $rows): array
    {
        $buckets = [];
        $userMrrByBucket = [];
        $indexByDate = [];

        foreach ($rows as $row) {
            $key = $row['date_clear'];

            if (! isset($indexByDate[$key])) {
                $indexByDate[$key] = count($buckets);
                $buckets[] = ['date' => $row['date'], 'date_clear' => $key];
                $userMrrByBucket[] = [];
            }

            if ($row['stats_user_id'] !== null) {
                $userMrrByBucket[$indexByDate[$key]][$row['stats_user_id']] = round((float) $row['mrr'], 2);
            }
        }

        return [$buckets, $userMrrByBucket];
    }

    private function nowCap(): string
    {
        return now()->format(StatsRangeType::DATE_FORMAT);
    }

    /**
     * @param  array<string, mixed>  $filterConditions
     * @return array<string, mixed>
     */
    private function withNowCap(array $filterConditions): array
    {
        return array_merge($filterConditions, ['nowCap' => $this->nowCap()]);
    }

    /**
     * @param  array<string, mixed>  $filterConditions
     */
    private function computeMrrAtDate(array $filterConditions, string $asOfDate): float
    {
        $conditions = $this->snapshotWindowConditions($filterConditions, $asOfDate);

        $builder = new MrrSnapshotSqlBuilder;
        $sql = strtr($builder->build(), $this->formatFilter($conditions));

        $result = $this->getDataArray($sql);

        return round((float) ($result[0]['mrr'] ?? 0), 2);
    }

    /**
     * Active MRR broken down by subscription_plan, taken as a point-in-time
     * snapshot at the now-capped range end (the same instant as the headline MRR
     * tile). Channel/property filters narrow it like every other MRR surface.
     *
     * @return array<int, array{plan: string|null, subscribers: int, mrr: float}>
     */
    public function getMrrByPlan(StatsFilterDto $filters): array
    {
        $filterConditions = $this->filterConditionsBuilder->build($filters);

        $asOfDate = min($filterConditions['endDateEnd'], $this->nowCap());
        $conditions = $this->snapshotWindowConditions($filterConditions, $asOfDate);

        $builder = new MrrByPlanSqlBuilder;
        $sql = strtr($builder->build(), $this->formatFilter($conditions));

        return array_map(fn (array $row): array => [
            'plan' => $row['plan'] ?? null,
            'subscribers' => (int) $row['subscribers'],
            'mrr' => round((float) $row['mrr'], 2),
        ], $this->getDataArray($sql));
    }

    /**
     * The active-subscription window for an as-of snapshot: a monthly sub counts
     * while its last payment is within a month (plus grace), a yearly one within a
     * year (plus grace). Shared by the total snapshot and the per-plan breakdown.
     *
     * @param  array<string, mixed>  $filterConditions
     * @return array<string, mixed>
     */
    private function snapshotWindowConditions(array $filterConditions, string $asOfDate): array
    {
        $asOf = CarbonImmutable::parse($asOfDate);
        $grace = StatsService::MRR_ACTIVE_GRACE_DAYS;

        return array_merge($filterConditions, [
            'asOfDate' => $asOfDate,
            'monthWindowStart' => $asOf->subMonth()->subDays($grace)->format(StatsRangeType::DATE_FORMAT),
            'yearWindowStart' => $asOf->subYear()->subDays($grace)->format(StatsRangeType::DATE_FORMAT),
        ]);
    }
}
