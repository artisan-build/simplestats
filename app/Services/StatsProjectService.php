<?php

namespace App\Services;

use App\Enums\StatsRange;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class StatsProjectService
{
    public function __construct(
        private StatsService $statsService
    ) {}

    public function getStats(array $filters = [])
    {
        $stats = $this->getRaw($filters);

        $this->groupStats($stats);
        $this->calcStatsTotal($stats);

        $this->applyStatsDetails($stats);

        return $stats;
    }

    public function getRaw(array $filters = [])
    {
//        ray()->showQueries();
        $sql = "
        WITH RECURSIVE date_sequence AS (
            SELECT ':startDate' AS date
            UNION ALL
            SELECT DATE_ADD(date, INTERVAL 1 :intervalType)
            FROM date_sequence
            WHERE date < ':endDate'
        ),
        pd_reg AS (
            SELECT DATE_FORMAT(time, ':dateFormat') AS date, COUNT(DISTINCT id, instance_id) as count
            FROM stats_users
            WHERE DATE(time) >= ':startDate' AND DATE(time) <= ':endDate' :projectInstanceCondition
            GROUP BY date
        ),
        # nau: Number of unique accounts who returned on this day (=2nd login) after the registration (=1st login)
        pd_nau AS (
            SELECT DATE_FORMAT(time, ':dateFormat') AS date, COUNT(DISTINCT stats_user_id, instance_id) AS count
            FROM stats_logins
            WHERE DATE(time) >= ':startDate' AND DATE(time) <= ':endDate' and counter = 2 :projectInstanceCondition
            GROUP BY date
        ),
        # napu: Number of unique accounts who did their first payment on this day
        pd_napu AS (
            SELECT DATE_FORMAT(time, ':dateFormat') AS date, COUNT(DISTINCT stats_user_id, instance_id) AS count
            FROM stats_payments
            WHERE DATE(time) >= ':startDate' AND DATE(time) <= ':endDate' and counter = 1 :projectInstanceCondition
            GROUP BY date
        ),
        # dau: Number of unique accounts who logged in on this day (without 1st login = registration)
        pd_dau AS (
            SELECT DATE_FORMAT(time, ':dateFormat') AS date, COUNT(DISTINCT stats_user_id, instance_id) AS count
            FROM stats_logins
            WHERE DATE(time) >= ':startDate' AND DATE(time) <= ':endDate' and counter != 1 :projectInstanceCondition
            GROUP BY date
        ),
        # dapu: Number of unique accounts who logged in or registered on this day and did at least one payment over lifetime (including today)
        pd_dapu AS (
            SELECT DATE_FORMAT(time, ':dateFormat') AS date, COUNT(DISTINCT stats_user_id, instance_id) AS count
            FROM stats_logins
            WHERE DATE(time) >= ':startDate' AND DATE(time) <= ':endDate' AND EXISTS (
                SELECT 1
                FROM stats_payments
                WHERE stats_logins.stats_user_id = stats_payments.stats_user_id
                AND stats_logins.instance_id = stats_payments.instance_id
            ) :projectInstanceCondition
            GROUP BY date
        ),
        # daapu: Number of unique accounts who logged in or registered on this day and did at least one payment in the last 20 days (including today)
        pd_daapu AS (
            SELECT DATE_FORMAT(time, ':dateFormat') AS date, COUNT(DISTINCT stats_user_id, instance_id) AS count
            FROM stats_logins
            WHERE DATE(time) >= ':startDate' AND DATE(time) <= ':endDate' AND EXISTS (
                SELECT 1
                FROM stats_payments
                WHERE stats_logins.stats_user_id = stats_payments.stats_user_id
                AND stats_logins.instance_id = stats_payments.instance_id
                AND DATE(stats_payments.time) >= DATE_SUB(DATE(stats_logins.time), INTERVAL 20 DAY)
                AND DATE(stats_payments.time) <= DATE(stats_logins.time)
            ) :projectInstanceCondition
            GROUP BY date
        ),
        # gross
        pd_gross AS (
            SELECT DATE_FORMAT(time, ':dateFormat') AS date, SUM(gross) AS count
            FROM stats_payments
            WHERE DATE(time) >= ':startDate' AND DATE(time) <= ':endDate' :projectInstanceCondition
            GROUP BY date
        ),
        # net
        pd_net AS (
            SELECT DATE_FORMAT(time, ':dateFormat') AS date, SUM(net) AS count
            FROM stats_payments
            WHERE DATE(time) >= ':startDate' AND DATE(time) <= ':endDate' :projectInstanceCondition
            GROUP BY date
        ),
        # lifetime regs
        lt_reg AS (
            SELECT
                DATE_FORMAT(ds.date, ':dateFormat') AS date,
                (SELECT COUNT(DISTINCT id, instance_id) FROM stats_users WHERE DATE(time) <= ds.date :projectInstanceCondition) AS count
            FROM date_sequence ds
        ),
        # lifetime paying user
        lt_pu AS (
            SELECT
                DATE_FORMAT(ds.date, ':dateFormat') AS date,
                (SELECT COUNT(DISTINCT stats_user_id, instance_id) FROM stats_payments WHERE DATE(time) <= ds.date :projectInstanceCondition) AS count
            FROM date_sequence ds
        ),
        # lifetime total actives
        lt_tau AS (
            SELECT
                DATE_FORMAT(ds.date, ':dateFormat') AS date,
                (SELECT COUNT(DISTINCT stats_user_id, instance_id) FROM stats_logins WHERE DATE(time) <= ds.date and counter = 2 :projectInstanceCondition) AS count
            FROM date_sequence ds
        ),
        # lifetime revenue
        lt_net AS (
            SELECT
                DATE_FORMAT(ds.date, ':dateFormat') AS date,
                (SELECT SUM(net) FROM stats_payments WHERE DATE(time) <= ds.date :projectInstanceCondition) AS count
            FROM date_sequence ds
        )
        SELECT
            DATE_FORMAT(ds.date, ':dateFormat') as date,
            COALESCE(pd_reg.count, 0) AS pd_REG,
            COALESCE(pd_nau.count, 0) AS pd_NAU,
            COALESCE(pd_napu.count, 0) AS pd_NAPU,
            COALESCE(pd_dau.count, 0) AS pd_DAU,
            COALESCE(pd_dapu.count, 0) AS pd_DAPU,
            COALESCE(pd_daapu.count, 0) AS pd_DAAPU,
            COALESCE(pd_gross.count, 0) AS pd_GROSS,
            COALESCE(pd_net.count, 0) AS pd_NET,
            COALESCE(lt_reg.count, 0) AS lt_REG,
            COALESCE(lt_pu.count, 0) AS lt_PU,
            COALESCE(lt_tau.count, 0) AS lt_TAU,
            COALESCE(lt_net.count, 0) AS lt_NET
        FROM
            date_sequence ds
            LEFT JOIN pd_reg ON DATE_FORMAT(ds.date, ':dateFormat') = pd_reg.date
            LEFT JOIN pd_nau ON DATE_FORMAT(ds.date, ':dateFormat') = pd_nau.date
            LEFT JOIN pd_napu ON DATE_FORMAT(ds.date, ':dateFormat') = pd_napu.date
            LEFT JOIN pd_dau ON DATE_FORMAT(ds.date, ':dateFormat') = pd_dau.date
            LEFT JOIN pd_dapu ON DATE_FORMAT(ds.date, ':dateFormat') = pd_dapu.date
            LEFT JOIN pd_daapu ON DATE_FORMAT(ds.date, ':dateFormat') = pd_daapu.date
            LEFT JOIN pd_gross ON DATE_FORMAT(ds.date, ':dateFormat') = pd_gross.date
            LEFT JOIN pd_net ON DATE_FORMAT(ds.date, ':dateFormat') = pd_net.date
            LEFT JOIN lt_reg ON DATE_FORMAT(ds.date, ':dateFormat') = lt_reg.date
            LEFT JOIN lt_pu ON DATE_FORMAT(ds.date, ':dateFormat') = lt_pu.date
            LEFT JOIN lt_tau ON DATE_FORMAT(ds.date, ':dateFormat') = lt_tau.date
            LEFT JOIN lt_net ON DATE_FORMAT(ds.date, ':dateFormat') = lt_net.date
        GROUP BY
            DATE_FORMAT(ds.date, ':dateFormat'),
            pd_reg.count,
            pd_nau.count,
            pd_napu.count,
            pd_dau.count,
            pd_dapu.count,
            pd_daapu.count,
            pd_gross.count,
            pd_net.count,
            lt_reg.count,
            lt_pu.count,
            lt_tau.count,
            lt_net.count
        ORDER BY
            DATE_FORMAT(ds.date, ':dateFormat') DESC;
        ";

        $sql = str_replace(
            [':startDate', ':endDate', ':intervalType', ':dateFormat', ' :projectInstanceCondition'],
            $this->getFilterConditions($filters),
            $sql
        );
//dd($sql);
        return DB::select($sql);
    }

    private function getFilterConditions(array $filters): array
    {
        $conditions = StatsRange::conditions($filters['range_start'], $filters['range_amount'])[$filters['range_type']];

        $projectInstanceCondition = (!empty($filters['project_id'])) ? " AND project_id = {$filters['project_id']} " : "" ;
        $projectInstanceCondition .= (!empty($filters['instance_id'])) ? " AND instance_id = {$filters['instance_id']} " : "" ;
        $conditions[] = $projectInstanceCondition;

        return $conditions;
    }

    private function groupStats(array &$stats): void
    {
        foreach ($stats as &$stat) {
            $stat = $this->statsService->groupStats($stat);

            $stat['stats']['date'] = $stat['date'];
            unset($stat['date']);
            $stat['stats']['per_date']['GROSS'] = round($stat['stats']['per_date']['GROSS'], 2);
            $stat['stats']['per_date']['FEE'] = round($stat['stats']['per_date']['GROSS'] - $stat['stats']['per_date']['NET'], 2);
            $stat['stats']['per_date']['ARPDAU'] = round($this->statsService->safeDivision($stat['stats']['per_date']['NET'], $stat['stats']['per_date']['DAU']), 2);
        } unset($stat);
    }

    private function calcStatsTotal(array &$stats): void
    {
        $statsTotal = [];

        foreach ($stats as $stat) {
            $this->statsService->calcStatsTotal($stat['stats'], $statsTotal);
        }

        $statsTotal['date'] = 'Total';
        $stats['total']['stats'] = $statsTotal;
    }

    private function applyStatsDetails(array &$stats): void
    {
        foreach ($stats as &$stat) {
            $stat['stats'] = $this->statsService->addStatsDetails($stat['stats']);
        } unset($stat);
    }

    public function getRangeTypes(): Collection
    {
        return collect(StatsRange::values())->transform(fn($name, $key) => [
            'id' => $name, 'name' => ucfirst($name)
        ]);
    }
}
