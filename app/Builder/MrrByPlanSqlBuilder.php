<?php

namespace App\Builder;

/**
 * Point-in-time MRR snapshot broken down by subscription_plan, grouped by plan so
 * the metric detail view can show MRR and subscriber count per plan. Shares the
 * active-window logic via activeSubscriptionsFrom() with the other MRR builders,
 * adding subscription_plan to the inner select for the grouping.
 */
class MrrByPlanSqlBuilder extends BaseSqlBuilder
{
    public function build(): string
    {
        return '
            SELECT
                lr.subscription_plan AS plan,
                COUNT(*) AS subscribers,
                COALESCE(SUM('.$this->mrrMonthlyAmount().'), 0) AS mrr'
            .$this->activeSubscriptionsFrom("<= ':asOfDate'", "':monthWindowStart'", "':yearWindowStart'", ['stats.subscription_plan']).'
            GROUP BY lr.subscription_plan
            ORDER BY mrr DESC';
    }
}
