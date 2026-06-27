<?php

namespace App\Builder;

use App\Enums\SubscriptionInterval;

/**
 * Point-in-time MRR snapshot broken down by subscription_plan. Same active-window
 * logic as MrrSnapshotSqlBuilder (latest subscription per user up to :asOfDate,
 * counted only inside its interval window, yearly normalised to net / 12), but
 * grouped by plan so the metric detail view can show MRR and subscriber count per
 * plan. The user join (:trackingUserJoinConditions) keeps channel/property filters
 * working, so each plan row respects the active StatsFilter.
 */
class MrrByPlanSqlBuilder extends BaseSqlBuilder
{
    public function build(): string
    {
        $month = SubscriptionInterval::MONTH->value;
        $year = SubscriptionInterval::YEAR->value;

        return "
            WITH latest_subscription AS (
                SELECT DISTINCT ON (stats.stats_user_id)
                    stats.stats_user_id,
                    stats.net,
                    stats.subscription_interval,
                    stats.subscription_plan,
                    stats.time
                FROM stats_payments stats
                :trackingUserJoinConditions
                WHERE :projectCondition
                    AND stats.subscription_interval IS NOT NULL
                    AND stats.stats_user_id IS NOT NULL
                    AND stats.time <= ':asOfDate'
                ORDER BY stats.stats_user_id, stats.time DESC
            )
            SELECT
                subscription_plan AS plan,
                COUNT(*) AS subscribers,
                COALESCE(SUM(
                    CASE WHEN subscription_interval = '$year' THEN net / 12.0 ELSE net END
                ), 0) AS mrr
            FROM latest_subscription
            WHERE (subscription_interval = '$month' AND time > ':monthWindowStart')
                OR (subscription_interval = '$year' AND time > ':yearWindowStart')
            GROUP BY subscription_plan
            ORDER BY mrr DESC
        ";
    }
}
