<?php

namespace App\Builder;

/**
 * Median days from registration to first payment, as a single value over the range.
 *
 * Anchored on stats_users (aliased `stats`) so the shared :projectCondition /
 * :trackingCondition fragments apply verbatim and the metric is attributable per
 * acquisition channel (the registration carries the tracking codes). The first
 * payment is the counter = 1 row (same definition as the paying-users metric);
 * restricting that payment to the range makes the cohort "customers who started
 * paying in this period", which avoids right-censoring (they have definitely paid).
 * PERCENTILE_CONT returns null when no user qualifies. Uncached.
 */
class TimeToPaySqlBuilder extends BaseSqlBuilder
{
    public function build(): string
    {
        return '
            SELECT '.$this->medianDaysBetween('stats.time', 'sp.time')." AS median_days
            FROM stats_users stats
            JOIN stats_payments sp
                ON sp.stats_user_id = stats.id
                AND sp.project_id = stats.project_id
                AND sp.counter = 1
                AND sp.time >= ':startDateStart'
                AND sp.time <= ':endDateEnd'
            WHERE :projectCondition
                AND stats.registered = true
                :trackingCondition
        ";
    }
}
