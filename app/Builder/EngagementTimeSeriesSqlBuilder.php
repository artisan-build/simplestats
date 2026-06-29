<?php

namespace App\Builder;

/**
 * Per-bucket engagement time series for the stickiness chart. Walks the same
 * date_sequence the period aggregation uses and, at the END of every bucket
 * (capped at now so a running bucket is measured as-of today), computes three
 * things via LATERAL subqueries:
 *
 *  - dau / mau: the rolling 30-day stickiness inputs as of the bucket end
 *    (avg daily active = distinct user-days / 30, monthly active = distinct
 *    users), so the caller can plot stickiness = dau / mau over time independent
 *    of the chosen granularity.
 *  - mau_prior / churned: the preceding 30-day window (30-60 days back) and how
 *    many of those users did not return in the current 30 days, so the caller can
 *    plot user churn = churned / mau_prior alongside stickiness.
 *
 * Active = any login (counter >= 1, including registration), matching the DAU
 * metric. The user join (:trackingUserJoinConditions) keeps the UTM/property
 * filters working, so the lines are attributable per channel. Uncached for now
 * (a LATERAL rolling window per bucket, like the MRR time series).
 */
class EngagementTimeSeriesSqlBuilder extends BaseSqlBuilder
{
    public function build(): string
    {
        $bucketEnd = '(LEAST('.$this->getDateInterval('add', 'ds.date_seq', 1, ':intervalType').", ':nowCap'::timestamp))";

        return '
            WITH RECURSIVE date_sequence AS (
                SELECT '.$this->getDateSelect().' AS date_seq
                UNION ALL
                SELECT '.$this->getDateInterval('add', 'date_seq', 1, ':intervalType')."
                FROM date_sequence
                WHERE date_seq < ':endDateStart'
            )
            SELECT
                ".$this->getDateFormat('ds.date_seq', ':dateFormat').' AS date,
                '.$this->getDateFormat('ds.date_seq', ':dateFormatClear')." AS date_clear,
                COALESCE(roll.user_days, 0)::float / 30 AS dau,
                COALESCE(roll.mau, 0) AS mau,
                COALESCE(churn.mau_prior, 0) AS mau_prior,
                COALESCE(churn.churned, 0) AS churned
            FROM date_sequence ds
            LEFT JOIN LATERAL (
                SELECT COUNT(*) AS user_days, COUNT(DISTINCT (ud.uid, ud.pid)) AS mau
                FROM (
                    SELECT DISTINCT stats.stats_user_id AS uid, stats.project_id AS pid, date_trunc('day', stats.time) AS d
                    FROM stats_logins stats
                    :trackingUserJoinConditions
                    WHERE :projectCondition
                        AND stats.counter >= 1
                        AND stats.time > $bucketEnd - INTERVAL '30 days'
                        AND stats.time <= $bucketEnd
                ) ud
            ) roll ON TRUE
            LEFT JOIN LATERAL (
                WITH window_logins AS (
                    SELECT stats.stats_user_id AS uid, stats.project_id AS pid, stats.time AS t
                    FROM stats_logins stats
                    :trackingUserJoinConditions
                    WHERE :projectCondition
                        AND stats.counter >= 1
                        AND stats.time > $bucketEnd - INTERVAL '60 days'
                        AND stats.time <= $bucketEnd
                ),
                prior AS (
                    SELECT DISTINCT uid, pid FROM window_logins WHERE t <= $bucketEnd - INTERVAL '30 days'
                ),
                cur AS (
                    SELECT DISTINCT uid, pid FROM window_logins WHERE t > $bucketEnd - INTERVAL '30 days'
                )
                SELECT
                    (SELECT COUNT(*) FROM prior) AS mau_prior,
                    (SELECT COUNT(*) FROM prior p
                        WHERE NOT EXISTS (SELECT 1 FROM cur c WHERE c.uid = p.uid AND c.pid = p.pid)
                    ) AS churned
            ) churn ON TRUE
            ORDER BY date_clear DESC
        ";
    }
}
