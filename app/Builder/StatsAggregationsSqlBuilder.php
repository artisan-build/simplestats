<?php

namespace App\Builder;

use App\Enums\TrackablePerson;
use App\Services\StatsService;

class StatsAggregationsSqlBuilder extends BaseSqlBuilder
{
    protected int $nauLoginCounter;

    public function __construct(
        protected TrackablePerson $trackablePerson
    ) {
        $this->nauLoginCounter = StatsService::NAU_LOGIN_COUNTER;
    }

    public function build(): string
    {
        $this->applySql($this->getCTE());

        if ($this->trackablePerson === TrackablePerson::USER) {
            // per date
            $this->applySql($this->getPD_REG());
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
        $this->applySql($this->getLT_GROSS_USER());
        $this->applySql($this->getLT_NET_USER());
        $this->applySql($this->getLT_NET_ALL(), false);

        $this->applySql($this->getSELECT(), false);
        $this->applySql($this->getFROM(), false);
        $this->applySql($this->getORDER(), false);

        return $this->sql;
    }

    protected function getCTE(): string
    {
        return "WITH RECURSIVE date_sequence AS (
            SELECT ':startDateStart' AS date
            UNION ALL
            SELECT DATE_ADD(date, INTERVAL 1 :intervalType)
            FROM date_sequence
            WHERE date < ':endDateStart'
        )";
    }

    /**
     * number of registrations
     */
    protected function getPD_REG(): string
    {
        return "pd_reg AS (
            SELECT DATE_FORMAT(stats.time, ':dateFormat') AS date, COUNT(DISTINCT stats.id, stats.instance_id) as count
            FROM stats_users stats
            WHERE :projectInstanceCondition AND stats.time >= ':startDateStart' AND stats.time <= ':endDateEnd' :trackingConditions
            GROUP BY date
        )";
    }

    /**
     * nau: Number of unique accounts who returned on this day (=2nd login) after the registration (=1st login)
     */
    protected function getPD_NAU(): string
    {
        return "pd_nau AS (
            SELECT DATE_FORMAT(stats.time, ':dateFormat') AS date, COUNT(DISTINCT stats.stats_user_id, stats.instance_id) AS count
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
        return "pd_napu AS (
            SELECT DATE_FORMAT(stats.time, ':dateFormat') AS date, COUNT(DISTINCT stats.stats_user_id, stats.instance_id) AS count
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
        return "pd_dau AS (
            SELECT DATE_FORMAT(stats.time, ':dateFormat') AS date, COUNT(DISTINCT stats.stats_user_id, stats.instance_id) AS count
            FROM stats_logins stats
            :trackingUserJoinConditions
            WHERE :projectInstanceCondition stats.time >= ':startDateStart' AND stats.time <= ':endDateEnd' AND stats.counter >= $this->nauLoginCounter
            GROUP BY date
        )";
    }

    /**
     * dapu: Number of unique accounts who logged in or registered on this day and did at least one payment over lifetime (including today)
     */
    protected function getPD_DAPU(): string
    {
        return "pd_dapu AS (
            SELECT DATE_FORMAT(stats.time, ':dateFormat') AS date, COUNT(DISTINCT stats.stats_user_id, stats.instance_id) AS count
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
        return "pd_daapu AS (
            SELECT DATE_FORMAT(stats.time, ':dateFormat') AS date, COUNT(DISTINCT stats.stats_user_id, stats.instance_id) AS count
            FROM stats_logins stats
            :trackingUserJoinConditions
            WHERE :projectInstanceCondition AND stats.time >= ':startDateStart' AND stats.time <= ':endDateEnd' AND EXISTS (
                SELECT 1
                FROM stats_payments sp
                WHERE stats.stats_user_id = sp.stats_user_id
                    AND stats.instance_id = sp.instance_id
                    AND sp.time >= DATE_SUB(stats.time, INTERVAL 20 DAY)
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
        return "lt_reg AS (
            SELECT
                DATE_FORMAT(ds.date, ':dateFormat') AS date,
                (
                    SELECT COUNT(DISTINCT stats.id, stats.instance_id)
                    FROM stats_users stats
                    WHERE :projectInstanceCondition AND stats.time <= :intervalEnd :trackingConditions
                ) AS count
            FROM date_sequence ds
        )";
    }

    /**
     * lifetime total actives
     */
    protected function getLT_TAU(): string
    {
        return "lt_tau AS (
            SELECT
                DATE_FORMAT(ds.date, ':dateFormat') AS date,
                (
                    SELECT COUNT(DISTINCT stats.stats_user_id, stats.instance_id)
                    FROM stats_logins stats
                    :trackingUserJoinConditions
                    WHERE :projectInstanceCondition AND stats.time <= :intervalEnd AND stats.counter = $this->nauLoginCounter
                ) AS count
            FROM date_sequence ds
        )";
    }

    /**
     * lifetime paying user
     */
    protected function getLT_PU(): string
    {
        return "lt_pu AS (
            SELECT
                DATE_FORMAT(ds.date, ':dateFormat') AS date,
                (
                    SELECT COUNT(DISTINCT stats.stats_user_id, stats.instance_id)
                    FROM stats_payments stats
                    :trackingUserJoinConditions
                    WHERE :projectInstanceCondition AND stats.time <= :intervalEnd
                ) AS count
            FROM date_sequence ds
        )";
    }

    protected function getPD_VISITORS(): string
    {
        return "pd_visitor AS (
            SELECT DATE_FORMAT(stats.time, ':dateFormat') AS date, COUNT(DISTINCT stats.id, stats.instance_id) as count
            FROM stats_visitors stats
            WHERE :projectInstanceCondition AND stats.time >= ':startDateStart' AND stats.time <= ':endDateEnd' :trackingConditions
            GROUP BY date
        )";
    }

    protected function getPD_GROSS(): string
    {
        return "pd_gross AS (
            SELECT date, SUM(count) as count
            FROM (
                SELECT DATE_FORMAT(stats.time, ':dateFormat') AS date, SUM(stats.gross) AS count
                FROM stats_payments stats
                :trackingUserJoinConditions
                WHERE :projectInstanceCondition AND stats.time >= ':startDateStart' AND stats.time <= ':endDateEnd'
                GROUP BY date
                UNION ALL
                SELECT DATE_FORMAT(stats.time, ':dateFormat') AS date, SUM(stats.gross) AS count
                FROM stats_payments stats
                :trackingVisitorJoinConditions
                WHERE :projectInstanceCondition AND stats.time >= ':startDateStart' AND stats.time <= ':endDateEnd'
                GROUP BY date
            ) as combined_results
            GROUP BY date
        )";
    }

    protected function getPD_NET(): string
    {
        return "pd_net AS (
            SELECT date, SUM(count) as count
            FROM (
                SELECT DATE_FORMAT(stats.time, ':dateFormat') AS date, SUM(stats.net) AS count
                FROM stats_payments stats
                :trackingUserJoinConditions
                WHERE :projectInstanceCondition AND stats.time >= ':startDateStart' AND stats.time <= ':endDateEnd'
                GROUP BY date
                UNION ALL
                SELECT DATE_FORMAT(stats.time, ':dateFormat') AS date, SUM(stats.net) AS count
                FROM stats_payments stats
                :trackingVisitorJoinConditions
                WHERE :projectInstanceCondition AND stats.time >= ':startDateStart' AND stats.time <= ':endDateEnd'
                GROUP BY date
            ) as combined_results
            GROUP BY date
        )";
    }

    protected function getLT_VISITORS(): string
    {
        return "lt_visitor AS (
            SELECT
                DATE_FORMAT(ds.date, ':dateFormat') AS date,
                (
                    SELECT COUNT(DISTINCT stats.id, stats.instance_id)
                    FROM stats_visitors stats
                    WHERE :projectInstanceCondition AND stats.time <= :intervalEnd :trackingConditions
                ) AS count
            FROM date_sequence ds
        )";
    }

    protected function getLT_GROSS_USER(): string
    {
        return "lt_gross_user AS (
            SELECT
                DATE_FORMAT(ds.date, ':dateFormat') AS date,
                (
                    SELECT SUM(stats.gross)
                    FROM stats_payments stats
                    :trackingUserJoinConditions
                    WHERE :projectInstanceCondition AND stats.time <= :intervalEnd
                ) AS count
            FROM date_sequence ds
        )";
    }

    protected function getLT_NET_USER(): string
    {
        return "lt_net_user AS (
            SELECT
                DATE_FORMAT(ds.date, ':dateFormat') AS date,
                (
                    SELECT SUM(stats.net)
                    FROM stats_payments stats
                    :trackingUserJoinConditions
                    WHERE :projectInstanceCondition AND stats.time <= :intervalEnd
                ) AS count
            FROM date_sequence ds
        )";
    }

    protected function getLT_NET_ALL(): string
    {
        return "lt_net_all AS (
            SELECT date, SUM(count) as count
            FROM (
                SELECT
                    DATE_FORMAT(ds.date, ':dateFormat') AS date,
                    (
                        SELECT SUM(stats.net)
                        FROM stats_payments stats
                        :trackingUserJoinConditions
                        WHERE :projectInstanceCondition AND stats.time <= :intervalEnd
                    ) AS count
                FROM date_sequence ds
                UNION ALL
                SELECT
                    DATE_FORMAT(ds.date, ':dateFormat') AS date,
                    (
                        SELECT SUM(stats.net)
                        FROM stats_payments stats
                        :trackingVisitorJoinConditions
                        WHERE :projectInstanceCondition AND stats.time <= :intervalEnd
                    ) AS count
                FROM date_sequence ds
            ) as combined_results
            GROUP BY date
        )";
    }

    protected function getSELECT(): string
    {
        $sql = "SELECT
            DATE_FORMAT(ds.date, ':dateFormat') as date,
            DATE_FORMAT(ds.date, ':dateFormatClear') as date_clear,";

        if ($this->trackablePerson === TrackablePerson::USER) {
            $sql .= '
                COALESCE(pd_reg.count, 0) AS pd_REG,
                COALESCE(pd_nau.count, 0) AS pd_NAU,
                COALESCE(pd_napu.count, 0) AS pd_NAPU,
                COALESCE(pd_dau.count, 0) AS pd_DAU,
                COALESCE(pd_dapu.count, 0) AS pd_DAPU,
                COALESCE(pd_daapu.count, 0) AS pd_DAAPU,
                COALESCE(lt_reg.count, 0) AS lt_REG,
                COALESCE(lt_pu.count, 0) AS lt_PU,
                COALESCE(lt_tau.count, 0) AS lt_TAU,';
        }

        $sql .= '
            COALESCE(pd_visitor.count, 0) AS pd_VISITOR,
            COALESCE(ROUND(pd_gross.count,2), 0) AS pd_GROSS,
            COALESCE(ROUND(pd_net.count,2), 0) AS pd_NET,
            COALESCE(lt_visitor.count, 0) AS lt_VISITOR,
            COALESCE(ROUND(lt_net_user.count,2), 0) AS lt_NET_USER,
            COALESCE(ROUND(lt_gross_user.count,2), 0) AS lt_GROSS_USER,
            COALESCE(ROUND(lt_net_all.count,2), 0) AS lt_NET_ALL';

        return $sql;
    }

    protected function getFROM(): string
    {
        $sql = 'FROM
            date_sequence ds';

        if ($this->trackablePerson === TrackablePerson::USER) {
            $sql .= "
                LEFT JOIN pd_reg ON DATE_FORMAT(ds.date, ':dateFormat') = pd_reg.date
                LEFT JOIN pd_nau ON DATE_FORMAT(ds.date, ':dateFormat') = pd_nau.date
                LEFT JOIN pd_napu ON DATE_FORMAT(ds.date, ':dateFormat') = pd_napu.date
                LEFT JOIN pd_dau ON DATE_FORMAT(ds.date, ':dateFormat') = pd_dau.date
                LEFT JOIN pd_dapu ON DATE_FORMAT(ds.date, ':dateFormat') = pd_dapu.date
                LEFT JOIN pd_daapu ON DATE_FORMAT(ds.date, ':dateFormat') = pd_daapu.date
                LEFT JOIN lt_reg ON DATE_FORMAT(ds.date, ':dateFormat') = lt_reg.date
                LEFT JOIN lt_pu ON DATE_FORMAT(ds.date, ':dateFormat') = lt_pu.date
                LEFT JOIN lt_tau ON DATE_FORMAT(ds.date, ':dateFormat') = lt_tau.date";
        }

        $sql .= "
            LEFT JOIN pd_visitor ON DATE_FORMAT(ds.date, ':dateFormat') = pd_visitor.date
            LEFT JOIN pd_gross ON DATE_FORMAT(ds.date, ':dateFormat') = pd_gross.date
            LEFT JOIN pd_net ON DATE_FORMAT(ds.date, ':dateFormat') = pd_net.date
            LEFT JOIN lt_visitor ON DATE_FORMAT(ds.date, ':dateFormat') = lt_visitor.date
            LEFT JOIN lt_net_user ON DATE_FORMAT(ds.date, ':dateFormat') = lt_net_user.date
            LEFT JOIN lt_gross_user ON DATE_FORMAT(ds.date, ':dateFormat') = lt_gross_user.date
            LEFT JOIN lt_net_all ON DATE_FORMAT(ds.date, ':dateFormat') = lt_net_all.date";

        return $sql;
    }

    protected function getORDER(): string
    {
        return "ORDER BY DATE_FORMAT(ds.date, ':dateFormat') DESC;";
    }
}
