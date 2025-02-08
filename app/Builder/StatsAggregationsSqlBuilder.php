<?php

namespace App\Builder;

use App\Builder\Drivers\AggregationBuilderType;
use App\Enums\TrackablePerson;
use App\Services\StatsService;

class StatsAggregationsSqlBuilder extends BaseSqlBuilder
{
    protected int $nauLoginCounter;

    public function __construct(
        protected TrackablePerson $trackablePerson,
        protected AggregationBuilderType $aggregationBuilderType,
    ) {
        $this->nauLoginCounter = StatsService::NAU_LOGIN_COUNTER;
    }

    public function build(): string
    {
        $this->applySql($this->getCTE());

        if ($this->trackablePerson === TrackablePerson::USER) {
            // per date
            $this->applySql($this->getPD_REG());
            $this->applySql($this->getPD_TAU());
            $this->applySql($this->getPD_PU());
            $this->applySql($this->getPD_NAU());
            $this->applySql($this->getPD_NAPU());
            $this->applySql($this->getPD_DAU());
            $this->applySql($this->getPD_DAPU());
            $this->applySql($this->getPD_DAAPU());
            // lifetime
            $this->applySql($this->getLT_REG());
            $this->applySql($this->getLT_TAU());
            $this->applySql($this->getLT_PU());
        }

        $this->applySql($this->getPD_VISITORS());
        $this->applySql($this->getPD_GROSS());
        $this->applySql($this->getPD_NET());

        $this->applySql($this->getLT_VISITORS());
        $this->applySql($this->getLT_NET(), false);

        $this->applySql($this->getSELECT(), false);
        $this->applySql($this->getFROM(), false);
        $this->applySql($this->getORDER(), false);

        return $this->sql;
    }

    // shorties
    protected function getDateTypeAnnotation(): string
    {
        return $this->aggregationBuilderType->getDateTypeAnnotation();
    }

    protected function getDateInterval(string $calc, string $date, int $amount, string $interval): string
    {
        return $this->aggregationBuilderType->getDateInterval(...func_get_args());
    }

    protected function getDistinctFields(array $fields)
    {
        return $this->aggregationBuilderType->getDistinctFields(...func_get_args());
    }

    protected function getDateFormat(string $date, string $format): string
    {
        return $this->aggregationBuilderType->getDateFormat(...func_get_args());
    }

    protected function getCTE(): string
    {
        return "WITH RECURSIVE date_sequence AS (
            SELECT ':startDateStart'".$this->getDateTypeAnnotation()." AS date
            UNION ALL
            SELECT ".$this->getDateInterval('add', 'date', 1, ':intervalType')."
            FROM date_sequence
            WHERE date < ':endDateStart'
        )";
    }

    /**
     * number of registrations
     */
    protected function getPD_REG(): string
    {
        return "pd_reg_cte AS (
            SELECT ".$this->getDateFormat('stats.time', ':dateFormat')." AS date, COUNT(DISTINCT ".$this->getDistinctFields(['stats.id', 'stats.instance_id']).") AS count
            FROM stats_users stats
            WHERE :projectInstanceCondition AND stats.time >= ':startDateStart' AND stats.time <= ':endDateEnd' :trackingConditions
            GROUP BY date
        )";
    }

    /**
     * number of total actives
     */
    protected function getPD_TAU(): string
    {
        return "pd_tau_cte AS (
            SELECT ".$this->getDateFormat('stats.time', ':dateFormat')." AS date, COUNT(DISTINCT ".$this->getDistinctFields(['stats.stats_user_id', 'stats.instance_id']).") AS count
            FROM stats_logins stats
            :trackingUserJoinConditions
            WHERE :projectInstanceCondition AND stats.time >= ':startDateStart' AND stats.time <= ':endDateEnd' AND stats.counter = $this->nauLoginCounter
            GROUP BY date
        )";
    }

    /**
     * paying user
     */
    protected function getPD_PU(): string
    {
        return "pd_pu_cte AS (
            SELECT ".$this->getDateFormat('stats.time', ':dateFormat')." AS date, COUNT(DISTINCT ".$this->getDistinctFields(['stats.stats_user_id', 'stats.instance_id']).") AS count
            FROM stats_payments stats
            :trackingUserJoinConditions
            WHERE :projectInstanceCondition AND stats.time >= ':startDateStart' AND stats.time <= ':endDateEnd' AND stats.counter = 1
            GROUP BY date
        )";
    }

    /**
     * nau: Number of unique accounts who returned on this day (=2nd login) after the registration (=1st login)
     */
    protected function getPD_NAU(): string
    {
        return "pd_nau_cte AS (
            SELECT ".$this->getDateFormat('stats.time', ':dateFormat')." AS date, COUNT(DISTINCT ".$this->getDistinctFields(['stats.stats_user_id', 'stats.instance_id']).") AS count
            FROM stats_logins stats
            :trackingUserJoinConditions
            WHERE :projectInstanceCondition AND stats.time >= ':startDateStart' AND stats.time <= ':endDateEnd' AND stats.counter = $this->nauLoginCounter
            GROUP BY date
        )";
    }

    /**
     * napu: Number of unique accounts who did their first payment on this day
     */
    protected function getPD_NAPU(): string
    {
        return "pd_napu_cte AS (
            SELECT ".$this->getDateFormat('stats.time', ':dateFormat')." AS date, COUNT(DISTINCT ".$this->getDistinctFields(['stats.stats_user_id', 'stats.instance_id']).") AS count
            FROM stats_payments stats
            :trackingUserJoinConditions
            WHERE :projectInstanceCondition AND stats.time >= ':startDateStart' AND stats.time <= ':endDateEnd' AND stats.counter = 1
            GROUP BY date
        )";
    }

    /**
     * dau: Number of unique accounts who logged in on this day (without 1st login = registration)
     */
    protected function getPD_DAU(): string
    {
        return "pd_dau_cte AS (
            SELECT ".$this->getDateFormat('stats.time', ':dateFormat')." AS date, COUNT(DISTINCT ".$this->getDistinctFields(['stats.stats_user_id', 'stats.instance_id']).") AS count
            FROM stats_logins stats
            :trackingUserJoinConditions
            WHERE :projectInstanceCondition AND stats.time >= ':startDateStart' AND stats.time <= ':endDateEnd' AND stats.counter >= $this->nauLoginCounter
            GROUP BY date
        )";
    }

    /**
     * dapu: Number of unique accounts who logged in or registered on this day and did at least one payment over lifetime (including today)
     */
    protected function getPD_DAPU(): string
    {
        return "pd_dapu_cte AS (
            SELECT ".$this->getDateFormat('stats.time', ':dateFormat')." AS date, COUNT(DISTINCT ".$this->getDistinctFields(['stats.stats_user_id', 'stats.instance_id']).") AS count
            FROM stats_logins stats
            :trackingUserJoinConditions
            WHERE :projectInstanceCondition AND stats.time >= ':startDateStart' AND stats.time <= ':endDateEnd' AND EXISTS (
                SELECT 1
                FROM stats_payments sp
                WHERE stats.stats_user_id = sp.stats_user_id
                    AND stats.instance_id = sp.instance_id
            )
            GROUP BY date
        )";
    }

    /**
     * daapu: Number of unique accounts who logged in or registered on this day and did at least one payment in the last 20 days (including today)
     */
    protected function getPD_DAAPU(): string
    {
        return "pd_daapu_cte AS (
            SELECT ".$this->getDateFormat('stats.time', ':dateFormat')." AS date, COUNT(DISTINCT ".$this->getDistinctFields(['stats.stats_user_id', 'stats.instance_id']).") AS count
            FROM stats_logins stats
            :trackingUserJoinConditions
            WHERE :projectInstanceCondition AND stats.time >= ':startDateStart' AND stats.time <= ':endDateEnd' AND EXISTS (
                SELECT 1
                FROM stats_payments sp
                WHERE stats.stats_user_id = sp.stats_user_id
                    AND stats.instance_id = sp.instance_id
                    AND sp.time >= ".$this->getDateInterval('sub', 'stats.time', 20, 'day')."
                    AND sp.time <= stats.time
            )
            GROUP BY date
        )";
    }

    /**
     * lifetime regs
     */
    protected function getLT_REG(): string
    {
        return "lt_reg_cte AS (
            SELECT
                ".$this->getDateFormat('ds.date', ':dateFormat')." AS date,
                (
                    SELECT COUNT(DISTINCT ".$this->getDistinctFields(['stats.id', 'stats.instance_id']).")
                    FROM stats_users stats
                    WHERE :projectInstanceCondition AND stats.time < ':startDateStart' :trackingConditions
                ) AS count
            FROM date_sequence ds
        )";
    }

    /**
     * lifetime total actives
     */
    protected function getLT_TAU(): string
    {
        return "lt_tau_cte AS (
            SELECT
                ".$this->getDateFormat('ds.date', ':dateFormat')." AS date,
                (
                    SELECT COUNT(DISTINCT ".$this->getDistinctFields(['stats.stats_user_id', 'stats.instance_id']).")
                    FROM stats_logins stats
                    :trackingUserJoinConditions
                    WHERE :projectInstanceCondition AND stats.time < ':startDateStart' AND stats.counter = $this->nauLoginCounter
                ) AS count
            FROM date_sequence ds
        )";
    }

    /**
     * lifetime paying user
     */
    protected function getLT_PU(): string
    {
        return "lt_pu_cte AS (
            SELECT
                ".$this->getDateFormat('ds.date', ':dateFormat')." AS date,
                (
                    SELECT COUNT(DISTINCT ".$this->getDistinctFields(['stats.stats_user_id', 'stats.instance_id']).")
                    FROM stats_payments stats
                    :trackingUserJoinConditions
                    WHERE :projectInstanceCondition AND stats.time < ':startDateStart' AND stats.counter = 1
                ) AS count
            FROM date_sequence ds
        )";
    }

    protected function getPD_VISITORS(): string
    {
        return "pd_visitors_cte AS (
            SELECT ".$this->getDateFormat('stats.time', ':dateFormat')." AS date, COUNT(*) AS count
            FROM stats_visitors stats
            WHERE :projectInstanceCondition AND stats.time >= ':startDateStart' AND stats.time <= ':endDateEnd' :trackingConditions
            GROUP BY date
        )";
    }

    protected function getPD_GROSS(): string
    {
        return "pd_gross_cte AS (
            SELECT date, SUM(count) AS count
            FROM (
                SELECT ".$this->getDateFormat('stats.time', ':dateFormat')." AS date, SUM(stats.gross) AS count
                FROM stats_payments stats
                :trackingUserJoinConditions
                WHERE :projectInstanceCondition AND stats.time >= ':startDateStart' AND stats.time <= ':endDateEnd'
                GROUP BY date
                UNION ALL
                SELECT ".$this->getDateFormat('stats.time', ':dateFormat')." AS date, SUM(stats.gross) AS count
                FROM stats_payments stats
                :trackingVisitorJoinConditions
                WHERE :projectInstanceCondition AND stats.time >= ':startDateStart' AND stats.time <= ':endDateEnd'
                GROUP BY date
            ) AS combined_results
            GROUP BY date
        )";
    }

    protected function getPD_NET(): string
    {
        return "pd_net_cte AS (
            SELECT date, SUM(count) AS count
            FROM (
                SELECT ".$this->getDateFormat('stats.time', ':dateFormat')." AS date, SUM(stats.net) AS count
                FROM stats_payments stats
                :trackingUserJoinConditions
                WHERE :projectInstanceCondition AND stats.time >= ':startDateStart' AND stats.time <= ':endDateEnd'
                GROUP BY date
                UNION ALL
                SELECT ".$this->getDateFormat('stats.time', ':dateFormat')." AS date, SUM(stats.net) AS count
                FROM stats_payments stats
                :trackingVisitorJoinConditions
                WHERE :projectInstanceCondition AND stats.time >= ':startDateStart' AND stats.time <= ':endDateEnd'
                GROUP BY date
            ) AS combined_results
            GROUP BY date
        )";
    }

    protected function getLT_VISITORS(): string
    {
        return "lt_visitor_cte AS (
            SELECT
                ".$this->getDateFormat('ds.date', ':dateFormat')." AS date,
                (
                    SELECT COUNT(*)
                    FROM stats_visitors stats
                    WHERE :projectInstanceCondition AND stats.time < ':startDateStart' :trackingConditions
                ) AS count
            FROM date_sequence ds
        )";
    }

    protected function getLT_NET(): string
    {
        return "lt_net_cte AS (
            SELECT date, SUM(count) AS count
            FROM (
                SELECT
                    ".$this->getDateFormat('ds.date', ':dateFormat')." AS date,
                    (
                        SELECT SUM(stats.net)
                        FROM stats_payments stats
                        :trackingUserJoinConditions
                        WHERE :projectInstanceCondition AND stats.time < ':startDateStart'
                    ) AS count
                FROM date_sequence ds
                UNION ALL
                SELECT
                    ".$this->getDateFormat('ds.date', ':dateFormat')." AS date,
                    (
                        SELECT SUM(stats.net)
                        FROM stats_payments stats
                        :trackingVisitorJoinConditions
                        WHERE :projectInstanceCondition AND stats.time < ':startDateStart'
                    ) AS count
                FROM date_sequence ds
            ) AS combined_results
            GROUP BY date
        )";
    }

    protected function getSELECT(): string
    {
        $sql = "SELECT
            ".$this->getDateFormat('ds.date', ':dateFormat')." AS date,
            ".$this->getDateFormat('ds.date', ':dateFormatClear')." AS date_clear,";

        if ($this->trackablePerson === TrackablePerson::USER) {
            $sql .= '
                COALESCE(pd_reg_cte.count, 0) AS pd_reg,
                COALESCE(pd_tau_cte.count, 0) AS pd_tau,
                COALESCE(pd_pu_cte.count, 0) AS pd_pu,
                COALESCE(pd_nau_cte.count, 0) AS pd_nau,
                COALESCE(pd_napu_cte.count, 0) AS pd_napu,
                COALESCE(pd_dau_cte.count, 0) AS pd_dau,
                COALESCE(pd_dapu_cte.count, 0) AS pd_dapu,
                COALESCE(pd_daapu_cte.count, 0) AS pd_daapu,
                COALESCE(lt_reg_cte.count, 0) AS lt_reg,
                COALESCE(lt_pu_cte.count, 0) AS lt_pu,
                COALESCE(lt_tau_cte.count, 0) AS lt_tau,';
        }

        $sql .= '
            COALESCE(pd_visitors_cte.count, 0) AS pd_visitor,
            COALESCE(pd_gross_cte.count, 0) AS pd_gross,
            COALESCE(pd_net_cte.count, 0) AS pd_net,
            COALESCE(lt_visitor_cte.count, 0) AS lt_visitor,
            COALESCE(lt_net_cte.count, 0) AS lt_net';

        return $sql;
    }

    protected function getFROM(): string
    {
        $sql = 'FROM
            date_sequence ds';

        if ($this->trackablePerson === TrackablePerson::USER) {
            $sql .= "
                LEFT JOIN pd_reg_cte ON ".$this->getDateFormat('ds.date', ':dateFormat')." = pd_reg_cte.date
                LEFT JOIN pd_tau_cte ON ".$this->getDateFormat('ds.date', ':dateFormat')." = pd_tau_cte.date
                LEFT JOIN pd_pu_cte ON ".$this->getDateFormat('ds.date', ':dateFormat')." = pd_pu_cte.date
                LEFT JOIN pd_nau_cte ON ".$this->getDateFormat('ds.date', ':dateFormat')." = pd_nau_cte.date
                LEFT JOIN pd_napu_cte ON ".$this->getDateFormat('ds.date', ':dateFormat')." = pd_napu_cte.date
                LEFT JOIN pd_dau_cte ON ".$this->getDateFormat('ds.date', ':dateFormat')." = pd_dau_cte.date
                LEFT JOIN pd_dapu_cte ON ".$this->getDateFormat('ds.date', ':dateFormat')." = pd_dapu_cte.date
                LEFT JOIN pd_daapu_cte ON ".$this->getDateFormat('ds.date', ':dateFormat')." = pd_daapu_cte.date
                LEFT JOIN lt_reg_cte ON ".$this->getDateFormat('ds.date', ':dateFormat')." = lt_reg_cte.date
                LEFT JOIN lt_pu_cte ON ".$this->getDateFormat('ds.date', ':dateFormat')." = lt_pu_cte.date
                LEFT JOIN lt_tau_cte ON ".$this->getDateFormat('ds.date', ':dateFormat')." = lt_tau_cte.date";
        }

        $sql .= "
            LEFT JOIN pd_visitors_cte ON ".$this->getDateFormat('ds.date', ':dateFormat')." = pd_visitors_cte.date
            LEFT JOIN pd_gross_cte ON ".$this->getDateFormat('ds.date', ':dateFormat')." = pd_gross_cte.date
            LEFT JOIN pd_net_cte ON ".$this->getDateFormat('ds.date', ':dateFormat')." = pd_net_cte.date
            LEFT JOIN lt_visitor_cte ON ".$this->getDateFormat('ds.date', ':dateFormat')." = lt_visitor_cte.date
            LEFT JOIN lt_net_cte ON ".$this->getDateFormat('ds.date', ':dateFormat')." = lt_net_cte.date";

        return $sql;
    }

    protected function getORDER(): string
    {
        return "ORDER BY date_clear DESC;";
    }
}
