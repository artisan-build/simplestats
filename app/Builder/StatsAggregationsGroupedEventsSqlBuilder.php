<?php

namespace App\Builder;

use App\Builder\Drivers\AggregationBuilderType;

class StatsAggregationsGroupedEventsSqlBuilder extends BaseSqlBuilder
{
    public function __construct(
        protected string $statsSort,
        protected AggregationBuilderType $aggregationBuilderType,
    ) {}

    public function build(): string
    {
        $this->applySql($this->getCTE());
        $this->applySql($this->getTotalVisitors());
        $this->applySql($this->getEventStats());
        $this->applySql($this->getNet(), false);

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
     * Total unique visitors in the project for the time period.
     */
    protected function getTotalVisitors(): string
    {
        return "total_visitors AS (
            SELECT COUNT(*) as count
            FROM stats_visitors stats
            WHERE :projectCondition AND stats.time >= ':startDateStart' AND stats.time <= ':endDateEnd' :trackingConditions
        )";
    }

    /**
     * Unique visitors/users who triggered each event.
     */
    protected function getEventStats(): string
    {
        return "event_stats AS (
            SELECT stats.stats_custom_event_name_id as type_id,
                COUNT(DISTINCT COALESCE(stats.stats_user_id, CAST(stats.stats_visitor_id AS varchar))) as triggered
            FROM stats_custom_events stats
            WHERE :projectCondition AND stats.time >= ':startDateStart' AND stats.time <= ':endDateEnd' :eventFilterEventName :trackingFilterForEvents
            GROUP BY type_id
        )";
    }

    /**
     * Net revenue from users/visitors who triggered each event.
     */
    protected function getNet(): string
    {
        return "event_net AS (
            SELECT type_id, SUM(count) as count
            FROM (
                SELECT ce.stats_custom_event_name_id as type_id, SUM(stats.net) as count
                FROM stats_payments stats
                INNER JOIN stats_custom_events ce ON ce.stats_user_id = stats.stats_user_id AND ce.project_id = stats.project_id
                WHERE :projectCondition AND stats.time >= ':startDateStart' AND stats.time <= ':endDateEnd' :eventFilterEventNameCe :trackingFilterForEventsCe
                GROUP BY type_id
                UNION ALL
                SELECT ce.stats_custom_event_name_id as type_id, SUM(stats.net) as count
                FROM stats_payments stats
                INNER JOIN stats_custom_events ce ON ce.stats_visitor_id = stats.stats_visitor_id AND ce.project_id = stats.project_id
                WHERE :projectCondition AND stats.time >= ':startDateStart' AND stats.time <= ':endDateEnd' :eventFilterEventNameCe :trackingFilterForEventsCe
                GROUP BY type_id
            ) as combined_results
            GROUP BY type_id
        )";
    }

    protected function getSELECT(): string
    {
        return 'SELECT
            sg.type_id as type_id,
            sg.name as name,
            COALESCE((SELECT count FROM total_visitors), 0) AS visitors,
            COALESCE(es.triggered, 0) AS triggered,
            ROUND(COALESCE((es.triggered * 100.0 / NULLIF((SELECT count FROM total_visitors), 0)), 0), 2) AS cr,
            COALESCE(event_net.count, 0) AS net';
    }

    protected function getFROM(): string
    {
        return 'FROM
            stats_groups sg
            LEFT JOIN event_stats es ON sg.type_id = es.type_id
            LEFT JOIN event_net ON sg.type_id = event_net.type_id';
    }

    protected function getORDER(): string
    {
        $sort = in_array($this->statsSort, ['visitors', 'triggered', 'cr', 'net', 'name']) ? $this->statsSort : 'visitors';

        return $this->aggregationBuilderType->getEventsGroupedOrderBy($sort);
    }
}
