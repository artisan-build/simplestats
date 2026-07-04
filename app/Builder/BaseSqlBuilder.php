<?php

namespace App\Builder;

use App\Enums\SubscriptionInterval;
use App\Enums\TrackablePerson;

abstract class BaseSqlBuilder
{
    protected string $sql = '';

    abstract public function build(): string;

    /**
     * The single definition of "active MRR" shared by every MRR builder so the rule
     * cannot drift between them. Callers prepend their own projection and reference the
     * inner subquery via the `lr` alias.
     *
     * @param  string  $cutoff  time comparison including its operator, e.g. "<= ':asOfDate'" or "< $bucketEnd"
     */
    protected function activeSubscriptionsFrom(string $cutoff, string $monthWindowStart, string $yearWindowStart, array $extraColumns = []): string
    {
        $month = SubscriptionInterval::MONTH->value;
        $year = SubscriptionInterval::YEAR->value;

        $columns = implode(', ', [
            'stats.stats_user_id',
            'stats.net',
            'stats.subscription_interval',
            ...$extraColumns,
            'stats.time',
        ]);

        return "
            FROM (
                SELECT DISTINCT ON (stats.stats_user_id) $columns
                FROM stats_payments stats
                :trackingUserJoinConditions
                WHERE :projectCondition
                    AND stats.subscription_interval IS NOT NULL
                    AND stats.stats_user_id IS NOT NULL
                    AND stats.time $cutoff
                ORDER BY stats.stats_user_id, stats.time DESC
            ) lr
            WHERE (lr.subscription_interval = '$month' AND lr.time > $monthWindowStart)
                OR (lr.subscription_interval = '$year' AND lr.time > $yearWindowStart)";
    }

    protected function mrrMonthlyAmount(string $alias = 'lr'): string
    {
        $year = SubscriptionInterval::YEAR->value;

        return "CASE WHEN $alias.subscription_interval = '$year' THEN $alias.net / 12.0 ELSE $alias.net END";
    }

    protected function medianDaysBetween(string $start, string $end): string
    {
        return "PERCENTILE_CONT(0.5) WITHIN GROUP (ORDER BY EXTRACT(EPOCH FROM ($end - $start)) / 86400.0)";
    }

    protected function applySql(string $sql, bool $addComma = true): void
    {
        $this->sql .= $sql;

        if ($addComma) {
            $this->sql .= ',';
        }

        // break
        $this->sql .= '
        ';
    }

    protected function getDateSelect(): string
    {
        return "':startDateStart'::timestamp";
    }

    protected function getDateInterval(string $calc, string $date, int $amount, string $interval): string
    {
        $func = ($calc === 'add') ? '+' : '-';

        return "$date $func INTERVAL '$amount $interval'";
    }

    protected function getDistinctFields(array $fields): string
    {
        return '('.implode(', ', $fields).')';
    }

    protected function getDateFormat(string $date, string $format): string
    {
        return "TO_CHAR($date, '$format')";
    }

    protected function getGroupedOrderBy(string $sort, TrackablePerson $trackablePerson): string
    {
        $oder = "ORDER BY $sort DESC NULLS LAST, net DESC NULLS LAST";

        if ($trackablePerson === TrackablePerson::USER) {
            $oder .= ', reg DESC NULLS LAST';
        }

        $oder .= ', visitors DESC';

        return $oder;
    }

    protected function getEventsGroupedOrderBy(string $sort): string
    {
        return "ORDER BY {$sort} DESC NULLS LAST, triggered DESC NULLS LAST, visitors DESC";
    }
}
