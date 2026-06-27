<?php

namespace App\Builder;

use App\Enums\SubscriptionInterval;

/**
 * Point-in-time MRR snapshot. Unlike the period-summed (pd_*) and lifetime
 * (lt_*) families in StatsAggregationsSqlBuilder, MRR is the active recurring
 * revenue as of a single instant (:asOfDate) and can shrink through churn, so
 * it is computed standalone instead of as another aggregation column.
 *
 * Per user, the latest subscription payment up to :asOfDate is taken (DISTINCT ON).
 * It counts as active only while it sits inside its interval window
 * (:monthWindowStart / :yearWindowStart), with yearly normalised to a monthly
 * amount via net / 12. The user join (:trackingUserJoinConditions) keeps the
 * UTM/property filters working, so MRR is attributable per channel.
 */
class MrrSnapshotSqlBuilder extends BaseSqlBuilder
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
                    stats.time
                FROM stats_payments stats
                :trackingUserJoinConditions
                WHERE :projectCondition
                    AND stats.subscription_interval IS NOT NULL
                    AND stats.stats_user_id IS NOT NULL
                    AND stats.time <= ':asOfDate'
                ORDER BY stats.stats_user_id, stats.time DESC
            )
            SELECT COALESCE(SUM(
                CASE WHEN subscription_interval = '$year' THEN net / 12.0 ELSE net END
            ), 0) AS mrr
            FROM latest_subscription
            WHERE (subscription_interval = '$month' AND time > ':monthWindowStart')
                OR (subscription_interval = '$year' AND time > ':yearWindowStart')
        ";
    }
}
