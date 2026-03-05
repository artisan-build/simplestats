<?php

namespace App\Builder;

use App\Builder\Drivers\AggregationBuilderType;

class StatsAggregationsGroupedEventsSqlBuilder extends BaseSqlBuilder
{
    public function __construct(
        protected string $statsSort,
        protected AggregationBuilderType $aggregationBuilderType,
    ) {
    }

    public function build(): string
    {
        $this->applySql($this->getCTE());
        $this->applySql($this->getUniques());
        $this->applySql($this->getTotal(), false);

        $this->applySql($this->getSELECT(), false);
        $this->applySql($this->getFROM(), false);
        $this->applySql($this->getORDER(), false);

        return $this->sql;
    }

    protected function getCTE(): string
    {
        return 'WITH stats_groups AS (
            SELECT id as type_id, name FROM stats_custom_event_names stats WHERE :teamCondition
        )';
    }

    /**
     * Unique visitors/users who triggered each event at least once.
     */
    protected function getUniques(): string
    {
        return "visitors AS (
            SELECT stats.stats_custom_event_name_id as type_id, COUNT(DISTINCT COALESCE(stats.stats_user_id, CAST(stats.stats_visitor_id AS varchar))) as count
            FROM stats_custom_events stats
            WHERE :projectCondition AND stats.time >= ':startDateStart' AND stats.time <= ':endDateEnd' :eventFilterEventName :trackingFilterForEvents
            GROUP BY type_id
        )";
    }

    /**
     * Total number of times each event was fired.
     */
    protected function getTotal(): string
    {
        return "total AS (
            SELECT stats.stats_custom_event_name_id as type_id, COUNT(*) as count
            FROM stats_custom_events stats
            WHERE :projectCondition AND stats.time >= ':startDateStart' AND stats.time <= ':endDateEnd' :eventFilterEventName :trackingFilterForEvents
            GROUP BY type_id
        )";
    }

    protected function getSELECT(): string
    {
        return 'SELECT
            sg.type_id as type_id,
            sg.name as name,
            COALESCE(visitors.count, 0) AS visitors,
            COALESCE(total.count, 0) AS total,
            ROUND(COALESCE((visitors.count * 100.0 / NULLIF(total.count, 0)), 0), 2) AS cr';
    }

    protected function getFROM(): string
    {
        return 'FROM
            stats_groups sg
            LEFT JOIN visitors ON sg.type_id = visitors.type_id
            LEFT JOIN total ON sg.type_id = total.type_id';
    }

    protected function getORDER(): string
    {
        $sort = in_array($this->statsSort, ['visitors', 'total', 'cr', 'name']) ? $this->statsSort : 'visitors';

        return "ORDER BY {$sort} DESC NULLS LAST, visitors DESC NULLS LAST, total DESC;";
    }
}
