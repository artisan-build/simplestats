<?php

namespace App\Builder;

/**
 * Per-bucket median days from first visit to registration.
 *
 * Anchored on stats_users (aliased `stats`) joined to the visitor it converted from
 * (stats.stats_visitor_id -> stats_visitors.id). Grouped by the registration bucket, so each row is
 * "for users who registered in this bucket, the median visit-to-registration gap".
 * Bucketing by the registration time means no right-censoring (the registration has
 * already happened). The shared :projectCondition / :trackingCondition apply verbatim,
 * giving per-channel attribution. Only buckets with linked registrations are emitted.
 * Uncached.
 */
class TimeToConvertSqlBuilder extends BaseSqlBuilder
{
    public function build(): string
    {
        // Group on the truncated bucket, not the raw timestamp: the clear date format is
        // finer than the bucket (e.g. YYYY-MM-DD for a month range), so grouping the raw
        // time would split one bucket into separate days.
        $bucket = "date_trunc(':intervalType', stats.time)";

        return '
            SELECT
                '.$this->getDateFormat($bucket, ':dateFormat').' AS date,
                '.$this->getDateFormat($bucket, ':dateFormatClear').' AS date_clear,
                '.$this->medianDaysBetween('sv.time', 'stats.time')." AS time_to_convert
            FROM stats_users stats
            JOIN stats_visitors sv
                ON sv.id = stats.stats_visitor_id
                AND sv.team_id = stats.team_id
                AND sv.project_id = stats.project_id
            WHERE :projectCondition
                AND stats.registered = true
                AND stats.time >= ':startDateStart'
                AND stats.time <= ':endDateEnd'
                :trackingCondition
            GROUP BY $bucket
            ORDER BY $bucket DESC
        ";
    }
}
