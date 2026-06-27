<?php

namespace App\Builder;

use App\Enums\SubscriptionInterval;
use App\Services\StatsService;

/**
 * Per-user active MRR at every bucket end, the matrix the movement
 * classification (New / Expansion / Contraction / Churn / Reactivation) is built
 * from: a movement is the change of a single user's MRR between bucket t-1 and t,
 * so the aggregate line alone is not enough.
 *
 * The sequence starts ONE interval before the range so the first visible bucket
 * has a previous baseline to diff against; that leading bucket is dropped by the
 * caller. Each bucket end re-runs the snapshot logic (DISTINCT ON per user,
 * interval + MRR_ACTIVE_GRACE_DAYS window, yearly normalised to net / 12). The
 * LATERAL is LEFT joined so empty buckets still emit a row (null user) and the
 * caller keeps every bucket in the grid.
 */
class MrrPerUserBucketSqlBuilder extends BaseSqlBuilder
{
    public function build(): string
    {
        $month = SubscriptionInterval::MONTH->value;
        $year = SubscriptionInterval::YEAR->value;
        $grace = StatsService::MRR_ACTIVE_GRACE_DAYS;

        $seed = $this->getDateInterval('sub', $this->getDateSelect(), 1, ':intervalType');
        // The running bucket ends in the future; cap at now so its churn window
        // is measured as-of today, not as-of the (future) calendar bucket end.
        $bucketEnd = '(LEAST('.$this->getDateInterval('add', 'ds.date_seq', 1, ':intervalType').", ':nowCap'::timestamp))";

        return "
            WITH RECURSIVE date_sequence AS (
                SELECT $seed AS date_seq
                UNION ALL
                SELECT ".$this->getDateInterval('add', 'date_seq', 1, ':intervalType')."
                FROM date_sequence
                WHERE date_seq < ':endDateStart'
            )
            SELECT
                ".$this->getDateFormat('ds.date_seq', ':dateFormat').' AS date,
                '.$this->getDateFormat('ds.date_seq', ':dateFormatClear')." AS date_clear,
                active.stats_user_id,
                active.mrr
            FROM date_sequence ds
            LEFT JOIN LATERAL (
                SELECT
                    lr.stats_user_id,
                    CASE WHEN lr.subscription_interval = '$year' THEN lr.net / 12.0 ELSE lr.net END AS mrr
                FROM (
                    SELECT DISTINCT ON (stats.stats_user_id)
                        stats.stats_user_id,
                        stats.net,
                        stats.subscription_interval,
                        stats.time
                    FROM stats_payments stats
                    :trackingUserJoinConditions
                    WHERE :projectCondition
                        AND stats.subscription_interval IS NOT NULL
                        AND stats.stats_user_id IS NOT NULL
                        AND stats.time < $bucketEnd
                    ORDER BY stats.stats_user_id, stats.time DESC
                ) lr
                WHERE (lr.subscription_interval = '$month' AND lr.time > ($bucketEnd - INTERVAL '1 month' - INTERVAL '$grace days'))
                    OR (lr.subscription_interval = '$year' AND lr.time > ($bucketEnd - INTERVAL '1 year' - INTERVAL '$grace days'))
            ) active ON TRUE
            ORDER BY date_clear ASC, active.stats_user_id
        ";
    }
}
