<?php

namespace App\Services;

use App\Models\Instance;
use App\Models\Project;
use Carbon\Carbon;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class StatsProjectService
{
    public function getStats(array $filters = [])
    {
        $project = $this->getRaw($filters);
dd($project);
        $this->groupStats($projects);
        $this->calcStatsTotalPerProject($projects);
        $this->applyStatsDetails($projects);

        return $projects;
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
        date_reg AS (
            SELECT DATE_FORMAT(time, ':dateFormat') AS date, COUNT(DISTINCT id, instance_id) as count
            FROM stats_users
            WHERE DATE(time) >= ':startDate' AND DATE(time) <= ':endDate'
            GROUP BY date
        ),
        # nau: Number of unique accounts who returned on this day (=2nd login) after the registration (=1st login)
        date_nau AS (
            SELECT DATE_FORMAT(time, ':dateFormat') AS date, COUNT(DISTINCT stats_user_id, instance_id) AS count
            FROM stats_logins
            WHERE DATE(time) >= ':startDate' AND DATE(time) <= ':endDate' and counter = 2
            GROUP BY date
        ),
        # napu: Number of unique accounts who did their first payment on this day
        date_napu AS (
            SELECT DATE_FORMAT(time, ':dateFormat') AS date, COUNT(DISTINCT stats_user_id, instance_id) AS count
            FROM stats_payments
            WHERE DATE(time) >= ':startDate' AND DATE(time) <= ':endDate' and counter = 1
            GROUP BY date
        ),
        # dau: Number of unique accounts who logged in on this day (without 1st login = registration)
        date_dau AS (
            SELECT DATE_FORMAT(time, ':dateFormat') AS date, COUNT(DISTINCT stats_user_id, instance_id) AS count
            FROM stats_logins
            WHERE DATE(time) >= ':startDate' AND DATE(time) <= ':endDate' and counter != 1
            GROUP BY date
        ),
        # dapu: Number of unique accounts who logged in or registered on this day and did at least one payment over lifetime (including today)
        date_dapu AS (
            SELECT DATE_FORMAT(time, ':dateFormat') AS date, COUNT(DISTINCT stats_user_id, instance_id) AS count
            FROM stats_logins
            WHERE DATE(time) >= ':startDate' AND DATE(time) <= ':endDate' AND EXISTS (
                SELECT 1
                FROM stats_payments
                WHERE stats_logins.stats_user_id = stats_payments.stats_user_id
                AND stats_logins.instance_id = stats_payments.instance_id
            )
            GROUP BY date
        ),
        # daapu: Number of unique accounts who logged in or registered on this day and did at least one payment in the last 20 days (including today)
        date_daapu AS (
            SELECT DATE_FORMAT(time, ':dateFormat') AS date, COUNT(DISTINCT stats_user_id, instance_id) AS count
            FROM stats_logins
            WHERE DATE(time) >= ':startDate' AND DATE(time) <= ':endDate' AND EXISTS (
                SELECT 1
                FROM stats_payments
                WHERE stats_logins.stats_user_id = stats_payments.stats_user_id
                AND stats_logins.instance_id = stats_payments.instance_id
                AND DATE(stats_payments.time) >= DATE_SUB(DATE(stats_logins.time), INTERVAL 20 DAY)
                AND DATE(stats_payments.time) <= DATE(stats_logins.time)
            )
            GROUP BY date
        ),
        # gross
        date_gross AS (
            SELECT DATE_FORMAT(time, ':dateFormat') AS date, SUM(gross) AS count
            FROM stats_payments
            WHERE DATE(time) >= ':startDate' AND DATE(time) <= ':endDate'
            GROUP BY date
        ),
        # net
        date_net AS (
            SELECT DATE_FORMAT(time, ':dateFormat') AS date, SUM(net) AS count
            FROM stats_payments
            WHERE DATE(time) >= ':startDate' AND DATE(time) <= ':endDate'
            GROUP BY date
        ),
        # lifetime regs
        lt_reg AS (
            SELECT
                DATE_FORMAT(ds.date, ':dateFormat') AS date,
                (SELECT COUNT(DISTINCT id, instance_id) FROM stats_users WHERE DATE(time) <= ds.date) AS count
            FROM date_sequence ds
        ),
        # lifetime paying user
        lt_pu AS (
            SELECT
                DATE_FORMAT(ds.date, ':dateFormat') AS date,
                (SELECT COUNT(DISTINCT stats_user_id, instance_id) FROM stats_payments WHERE DATE(time) <= ds.date) AS count
            FROM date_sequence ds
        ),
        # lifetime total actives
        lt_tau AS (
            SELECT
                DATE_FORMAT(ds.date, ':dateFormat') AS date,
                (SELECT COUNT(DISTINCT stats_user_id, instance_id) FROM stats_logins WHERE DATE(time) <= ds.date and counter = 2) AS count
            FROM date_sequence ds
        ),
        # lifetime revenue
        lt_rev AS (
            SELECT
                DATE_FORMAT(ds.date, ':dateFormat') AS date,
                (SELECT SUM(gross) FROM stats_payments WHERE DATE(time) <= ds.date) AS count
            FROM date_sequence ds
        )
        SELECT
            DATE_FORMAT(ds.date, ':dateFormat') as date,
            COALESCE(date_reg.count, 0) AS date_reg,
            COALESCE(date_nau.count, 0) AS date_nau,
            COALESCE(date_napu.count, 0) AS date_napu,
            COALESCE(date_dau.count, 0) AS date_dau,
            COALESCE(date_dapu.count, 0) AS date_dapu,
            COALESCE(date_daapu.count, 0) AS date_daapu,
            COALESCE(date_gross.count, 0) AS date_gross,
            COALESCE(date_net.count, 0) AS date_net,
            COALESCE(lt_reg.count, 0) AS lt_reg,
            COALESCE(lt_pu.count, 0) AS lt_pu,
            COALESCE(lt_tau.count, 0) AS lt_tau,
            COALESCE(lt_rev.count, 0) AS lt_rev
        FROM
            date_sequence ds
            LEFT JOIN date_reg ON DATE_FORMAT(ds.date, ':dateFormat') = date_reg.date
            LEFT JOIN date_nau ON DATE_FORMAT(ds.date, ':dateFormat') = date_nau.date
            LEFT JOIN date_napu ON DATE_FORMAT(ds.date, ':dateFormat') = date_napu.date
            LEFT JOIN date_dau ON DATE_FORMAT(ds.date, ':dateFormat') = date_dau.date
            LEFT JOIN date_dapu ON DATE_FORMAT(ds.date, ':dateFormat') = date_dapu.date
            LEFT JOIN date_daapu ON DATE_FORMAT(ds.date, ':dateFormat') = date_daapu.date
            LEFT JOIN date_gross ON DATE_FORMAT(ds.date, ':dateFormat') = date_gross.date
            LEFT JOIN date_net ON DATE_FORMAT(ds.date, ':dateFormat') = date_net.date
            LEFT JOIN lt_reg ON DATE_FORMAT(ds.date, ':dateFormat') = lt_reg.date
            LEFT JOIN lt_pu ON DATE_FORMAT(ds.date, ':dateFormat') = lt_pu.date
            LEFT JOIN lt_tau ON DATE_FORMAT(ds.date, ':dateFormat') = lt_tau.date
            LEFT JOIN lt_rev ON DATE_FORMAT(ds.date, ':dateFormat') = lt_rev.date
        GROUP BY
            DATE_FORMAT(ds.date, ':dateFormat'),
            date_reg.count,
            date_nau.count,
            date_napu.count,
            date_dau.count,
            date_dapu.count,
            date_daapu.count,
            date_gross.count,
            date_net.count,
            lt_reg.count,
            lt_pu.count,
            lt_tau.count,
            lt_rev.count
        ORDER BY
            DATE_FORMAT(ds.date, ':dateFormat') DESC;
        ";

        $range = match ($filters['range_type']) {
            'days' => [
                $filters['range_start']->startOfDay()->subDays($filters['range_amount']-1)->format('Y-m-d'),
                $filters['range_start']->endOfDay()->format('Y-m-d'),
                'DAY',
                '%Y-%m-%d'
            ],
            'weeks' => [
                $filters['range_start']->startOfWeek()->subWeeks($filters['range_amount']-2)->format('Y-m-d'),
                $filters['range_start']->endOfWeek()->format('Y-m-d'),
                'WEEK',
                '%Y-%v'
            ],
            'months' => [
                $filters['range_start']->startOfMonth()->subMonths($filters['range_amount']-2)->format('Y-m-d'),
                $filters['range_start']->endOfMonth()->format('Y-m-d'),
                'MONTH',
                '%Y-%m'
            ],
        };

        $sql = str_replace(
            [':startDate', ':endDate', ':intervalType', ':dateFormat'],
            $range,
            $sql
        );

        return DB::select($sql);
    }

    private function groupStats(Collection &$projects): void
    {
        $projects->transform(function (Project $project) {
            $project->instances->transform(function (Instance $instance) {
                $result = ['stats' => []];
                $statsPrefixes = ['lt_' => 'lifetime', 'date_' => 'date'];

                foreach ($instance->getAttributes() as $key => $value) {
                    if (Str::startsWith($key, array_keys($statsPrefixes))) {
                        $parts = explode('_', $key);
                        $statsKey = $statsPrefixes[$parts[0].'_'];
                        $result['stats'][$statsKey][$parts[1]] = $value;
                    } else {
                        $result[$key] = $value;
                    }
                }

                return $result;
            });

            return $project;
        });

    }

    private function calcStatsTotalPerProject(Collection &$projects): void
    {
        $projects->transform(function (Project $project) {
            $result = [];
            $project->instances->each(function ($instance) use (&$result) {
                foreach ($instance['stats'] as $statCategory => $stats) {
                    foreach ($stats as $key => $value) {
                        $result[$statCategory][$key] = ($result[$statCategory][$key] ?? 0) + $value;
                    }
                }
            });

            $project->stats = $result;

            return $project;
        });
    }

    private function applyStatsDetails(Collection $projects): void
    {
        $projects->transform(function (Project $project) {
            $project['stats'] = array_merge(['name' => 'Total'], $this->addStatsDetails($project['stats']));

            $project->instances->transform(function ($instance) {
                $instance['stats'] = array_merge(['name' => $instance['name']], $this->addStatsDetails($instance['stats']));
                return $instance;
            });

            return $project;
        });
    }

    private function addStatsDetails(array $stats): array
    {
        // Lifetime
        $stats['lifetime']['REV'] = round($stats['lifetime']['REV'], 2);
        $stats['lifetime']['REG2DAU'] = round($this->safeDivision($stats['date']['DAU'], $stats['lifetime']['REG']) * 100, 2);
        $stats['lifetime']['REG2PU'] = round($this->safeDivision($stats['lifetime']['PU'], $stats['lifetime']['REG']) * 100, 2);
        $stats['lifetime']['REG2TAU'] = round($this->safeDivision($stats['lifetime']['TAU'], $stats['lifetime']['REG']) * 100, 2);
        $stats['lifetime']['ARPU'] = round($this->safeDivision($stats['lifetime']['REV'], $stats['lifetime']['REG']), 2);
        $stats['lifetime']['ARPPU'] = round($this->safeDivision($stats['lifetime']['REV'], $stats['lifetime']['PU']), 2);

        // Yesterday
        $stats['date']['REV'] = round($stats['date']['REV'], 2);

        return $stats;
    }

    public function safeDivision($numerator, $denominator): float|int
    {
        return $denominator == 0 ? 0 : $numerator / $denominator;
    }
}
