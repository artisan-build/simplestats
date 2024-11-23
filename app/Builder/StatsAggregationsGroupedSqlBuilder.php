<?php

namespace App\Builder;

use App\Enums\TrackablePerson;
use App\Services\StatsService;

class StatsAggregationsGroupedSqlBuilder extends BaseSqlBuilder
{
    protected int $nauLoginCounter;

    public function __construct(
        protected string $statsTable,
        protected string $statsDBType,
        protected string $statsSort,
        protected string $typeIdFallback,
        protected TrackablePerson $trackablePerson
    ) {
        $this->nauLoginCounter = StatsService::NAU_LOGIN_COUNTER;
    }

    public function build(): string
    {
        $this->applySql($this->getCTE());

        if ($this->trackablePerson === TrackablePerson::USER) {
            $this->applySql($this->getPD_DAU());
            $this->applySql($this->getLT_REG());
        }

        $this->applySql($this->getLT_VISITORS());
        $this->applySql($this->getLT_NET(), false);

        $this->applySql($this->getSELECT(), false);
        $this->applySql($this->getFROM(), false);
        $this->applySql($this->getORDER(), false);

        return $this->sql;
    }

    protected function getCTE(): string
    {
        return "WITH stats_groups AS (
            SELECT id as type_id, name FROM $this->statsTable stats WHERE 1 :projectCondition
            UNION ALL
            SELECT $this->typeIdFallback as type_id, 'Direct / Unknown' as name
        )";
    }

    /**
     * dau: Number of unique accounts who logged in on this day (without 1st login = registration)
     */
    protected function getPD_DAU(): string
    {
        return "pd_dau AS (
            SELECT COALESCE(su.$this->statsDBType, $this->typeIdFallback) as type_id, COUNT(DISTINCT stats.stats_user_id, stats.instance_id) AS count
            FROM stats_logins stats
            :trackingJoinConditions :projectInstanceCondition
            WHERE stats.time >= ':dauEndDateStart' AND stats.time <= ':dauEndDateEnd' and stats.counter >= $this->nauLoginCounter :projectInstanceCondition
            GROUP BY su.$this->statsDBType
        )";
    }

    /**
     * lifetime regs
     */
    protected function getLT_REG(): string
    {
        return "lt_reg AS (
            SELECT COALESCE(stats.$this->statsDBType, $this->typeIdFallback) as type_id, COUNT(DISTINCT stats.id, stats.instance_id) as count
            FROM stats_users stats
            WHERE stats.time >= ':startDateStart' AND stats.time <= ':endDateEnd'
            :trackingConditions :projectInstanceCondition
            GROUP BY stats.$this->statsDBType
        )";
    }

    /**
     * lifetime visitors
     */
    protected function getLT_VISITORS(): string
    {
        return "lt_visitors AS (
            SELECT COALESCE(stats.$this->statsDBType, $this->typeIdFallback) as type_id, COUNT(DISTINCT stats.id, stats.instance_id) as count
            FROM stats_visitors stats
            WHERE stats.time >= ':startDateStart' AND stats.time <= ':endDateEnd'
            :trackingConditions :projectInstanceCondition
            GROUP BY stats.$this->statsDBType
        )";
    }

    /**
     * lifetime net revenue
     */
    protected function getLT_NET(): string
    {
        return "lt_net AS (
            SELECT COALESCE(su.$this->statsDBType, $this->typeIdFallback) as type_id, SUM(stats.net) as count
            FROM stats_payments stats
            :trackingJoinConditions :projectInstanceCondition
            WHERE stats.time >= ':startDateStart' AND stats.time <= ':endDateEnd' :projectInstanceCondition
            GROUP BY su.$this->statsDBType
        )";
    }

    protected function getSELECT(): string
    {
        $sql = "SELECT
            sg.type_id as type_id,
            sg.name as name,";

        if ($this->trackablePerson === TrackablePerson::USER) {
            $sql .= "
                COALESCE(lt_reg.count, 0) as reg,
                COALESCE(lt_reg.count / lt_visitors.count * 100, 0) as cr,
                COALESCE(pd_dau.count, 0) AS dau,";
        }

        $sql .= "
            COALESCE(lt_visitors.count, 0) AS visitors,
            COALESCE(ROUND(lt_net.count,2), 0) AS net";

        return $sql;
    }

    protected function getFROM(): string
    {
        $sql = "FROM
            stats_groups sg";

        if ($this->trackablePerson === TrackablePerson::USER) {
            $sql .= "
                LEFT JOIN pd_dau ON sg.type_id = pd_dau.type_id
                LEFT JOIN lt_reg ON sg.type_id = lt_reg.type_id";
        }

        $sql .= "
            LEFT JOIN lt_visitors ON sg.type_id = lt_visitors.type_id
            LEFT JOIN lt_net ON sg.type_id = lt_net.type_id";

        return $sql;
    }

    protected function getORDER(): string
    {
        return "ORDER BY
            $this->statsSort DESC;";
    }
}
