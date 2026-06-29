<?php

namespace App\Builder;

/**
 * Point-in-time MRR snapshot. Unlike the period-summed (pd_*) and lifetime
 * (lt_*) families in StatsAggregationsSqlBuilder, MRR is the active recurring
 * revenue as of a single instant (:asOfDate) and can shrink through churn, so
 * it is computed standalone instead of as another aggregation column.
 *
 * The active-subscription window logic is shared via activeSubscriptionsFrom()
 * so this stays in lockstep with the time-series, per-user and per-plan builders.
 */
class MrrSnapshotSqlBuilder extends BaseSqlBuilder
{
    public function build(): string
    {
        return '
            SELECT COALESCE(SUM('.$this->mrrMonthlyAmount().'), 0) AS mrr'
            .$this->activeSubscriptionsFrom("<= ':asOfDate'", "':monthWindowStart'", "':yearWindowStart'");
    }
}
