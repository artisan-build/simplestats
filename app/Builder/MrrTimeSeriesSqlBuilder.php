<?php

namespace App\Builder;

use App\Services\StatsService;

/**
 * Per-bucket MRR snapshot time series. Where MrrSnapshotSqlBuilder takes MRR at a
 * single instant, this walks the same date_sequence the period aggregation uses
 * and computes a standalone snapshot at the END of every bucket
 * (date_seq + one interval) via a LATERAL subquery.
 *
 * Each bucket re-runs the snapshot logic: per user the latest subscription payment
 * before the bucket end (DISTINCT ON), counted only while it sits inside its
 * interval window (interval + MRR_ACTIVE_GRACE_DAYS), yearly normalised to a
 * monthly amount (net / 12). The window is bucket-relative, so it lives in SQL
 * instead of being precomputed in PHP. The user join keeps UTM/property filters
 * working, so the MRR line is attributable per channel.
 *
 * MRR is a non-monotonic stock (a subscription falls out without an event once
 * its window lapses), so it is NOT carried forward like the lt_* families.
 */
class MrrTimeSeriesSqlBuilder extends BaseSqlBuilder
{
    public function build(): string
    {
        $grace = StatsService::MRR_ACTIVE_GRACE_DAYS;

        // The running bucket ends in the future; cap at now so its churn window
        // is measured as-of today, not as-of the (future) calendar bucket end.
        $bucketEnd = '(LEAST('.$this->getDateInterval('add', 'ds.date_seq', 1, ':intervalType').", ':nowCap'::timestamp))";
        $monthWindow = "($bucketEnd - INTERVAL '1 month' - INTERVAL '$grace days')";
        $yearWindow = "($bucketEnd - INTERVAL '1 year' - INTERVAL '$grace days')";

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
                '.$this->getDateFormat('ds.date_seq', ':dateFormatClear').' AS date_clear,
                COALESCE(snapshot.mrr, 0) AS mrr,
                COALESCE(snapshot.subscribers, 0) AS subscribers
            FROM date_sequence ds
            LEFT JOIN LATERAL (
                SELECT
                    COALESCE(SUM('.$this->mrrMonthlyAmount().'), 0) AS mrr,
                    COUNT(*) AS subscribers'
                    .$this->activeSubscriptionsFrom("< $bucketEnd", $monthWindow, $yearWindow).'
            ) snapshot ON TRUE
            ORDER BY date_clear DESC
        ';
    }
}
