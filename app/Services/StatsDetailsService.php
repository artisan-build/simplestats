<?php

namespace App\Services;

use App\DTOs\StatsRangeFilterDto;
use App\Enums\StatsRangeType;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class StatsDetailsService
{
    public function __construct(
        private StatsService $statsService
    ) {}

    public function getStats(StatsRangeFilterDto $filters)
    {
        $stats = $this->getRaw($filters);

        $this->groupStats($stats);
        $this->applyStatsTotal($stats);

        $this->applyStatsDetails($stats);

        return $stats;
    }

    public function getRaw(StatsRangeFilterDto $filters)
    {
        $nauLoginCounter = StatsService::NAU_LOGIN_COUNTER;

        $sql = "
        WITH RECURSIVE date_sequence AS (
            SELECT ':startDateStart' AS date
            UNION ALL
            SELECT DATE_ADD(date, INTERVAL 1 :intervalType)
            FROM date_sequence
            WHERE date < ':endDateStart'
        ),
        pd_reg AS (
            SELECT DATE_FORMAT(stats.time, ':dateFormat') AS date, COUNT(DISTINCT stats.id, stats.instance_id) as count
            FROM stats_users stats
            WHERE stats.time >= ':startDateStart' AND stats.time <= ':endDateEnd' :projectInstanceCondition :trackingConditions
            GROUP BY date
        ),
        # nau: Number of unique accounts who returned on this day (=2nd login) after the registration (=1st login)
        pd_nau AS (
            SELECT DATE_FORMAT(stats.time, ':dateFormat') AS date, COUNT(DISTINCT stats.stats_user_id, stats.instance_id) AS count
            FROM stats_logins stats
            :trackingJoinConditions
            WHERE stats.time >= ':startDateStart' AND stats.time <= ':endDateEnd' and stats.counter = $nauLoginCounter :projectInstanceCondition
            GROUP BY date
        ),
        # napu: Number of unique accounts who did their first payment on this day
        pd_napu AS (
            SELECT DATE_FORMAT(stats.time, ':dateFormat') AS date, COUNT(DISTINCT stats.stats_user_id, stats.instance_id) AS count
            FROM stats_payments stats
            :trackingJoinConditions
            WHERE stats.time >= ':startDateStart' AND stats.time <= ':endDateEnd' and stats.counter = 1 :projectInstanceCondition
            GROUP BY date
        ),
        # dau: Number of unique accounts who logged in on this day (without 1st login = registration)
        pd_dau AS (
            SELECT DATE_FORMAT(stats.time, ':dateFormat') AS date, COUNT(DISTINCT stats.stats_user_id, stats.instance_id) AS count
            FROM stats_logins stats
            :trackingJoinConditions
            WHERE stats.time >= ':startDateStart' AND stats.time <= ':endDateEnd' and stats.counter != 1 :projectInstanceCondition
            GROUP BY date
        ),
        # dapu: Number of unique accounts who logged in or registered on this day and did at least one payment over lifetime (including today)
        pd_dapu AS (
            SELECT DATE_FORMAT(stats.time, ':dateFormat') AS date, COUNT(DISTINCT stats.stats_user_id, stats.instance_id) AS count
            FROM stats_logins stats
            :trackingJoinConditions
            WHERE stats.time >= ':startDateStart' AND stats.time <= ':endDateEnd' AND EXISTS (
                SELECT 1
                FROM stats_payments sp
                WHERE stats.stats_user_id = sp.stats_user_id
                    AND stats.instance_id = sp.instance_id
            ) :projectInstanceCondition
            GROUP BY date
        ),
        # daapu: Number of unique accounts who logged in or registered on this day and did at least one payment in the last 20 days (including today)
        pd_daapu AS (
            SELECT DATE_FORMAT(stats.time, ':dateFormat') AS date, COUNT(DISTINCT stats.stats_user_id, stats.instance_id) AS count
            FROM stats_logins stats
            :trackingJoinConditions
            WHERE stats.time >= ':startDateStart' AND stats.time <= ':endDateEnd' AND EXISTS (
                SELECT 1
                FROM stats_payments sp
                WHERE stats.stats_user_id = sp.stats_user_id
                    AND stats.instance_id = sp.instance_id
                    AND sp.time >= DATE_SUB(stats.time, INTERVAL 20 DAY)
                    AND sp.time <= stats.time
            ) :projectInstanceCondition
            GROUP BY date
        ),
        # gross
        pd_gross AS (
            SELECT DATE_FORMAT(stats.time, ':dateFormat') AS date, SUM(stats.gross) AS count
            FROM stats_payments stats
            :trackingJoinConditions
            WHERE stats.time >= ':startDateStart' AND stats.time <= ':endDateEnd' :projectInstanceCondition
            GROUP BY date
        ),
        # net
        pd_net AS (
            SELECT DATE_FORMAT(stats.time, ':dateFormat') AS date, SUM(stats.net) AS count
            FROM stats_payments stats
            :trackingJoinConditions
            WHERE stats.time >= ':startDateStart' AND stats.time <= ':endDateEnd' :projectInstanceCondition
            GROUP BY date
        ),
        # lifetime regs
        lt_reg AS (
            SELECT
                DATE_FORMAT(ds.date, ':dateFormat') AS date,
                (
                    SELECT COUNT(DISTINCT stats.id, stats.instance_id)
                    FROM stats_users stats
                    WHERE stats.time <= :intervalEnd :projectInstanceCondition :trackingConditions
                ) AS count
            FROM date_sequence ds
        ),
        # lifetime paying user
        lt_pu AS (
            SELECT
                DATE_FORMAT(ds.date, ':dateFormat') AS date,
                (
                    SELECT COUNT(DISTINCT stats.stats_user_id, stats.instance_id)
                    FROM stats_payments stats
                    :trackingJoinConditions
                    WHERE stats.time <= :intervalEnd :projectInstanceCondition
                ) AS count
            FROM date_sequence ds
        ),
        # lifetime total actives
        lt_tau AS (
            SELECT
                DATE_FORMAT(ds.date, ':dateFormat') AS date,
                (
                    SELECT COUNT(DISTINCT stats.stats_user_id, stats.instance_id)
                    FROM stats_logins stats
                    :trackingJoinConditions
                    WHERE stats.time <= :intervalEnd and stats.counter = $nauLoginCounter :projectInstanceCondition
                ) AS count
            FROM date_sequence ds
        ),
        # lifetime net revenue
        lt_net AS (
            SELECT
                DATE_FORMAT(ds.date, ':dateFormat') AS date,
                (
                    SELECT SUM(stats.net)
                    FROM stats_payments stats
                    :trackingJoinConditions
                    WHERE stats.time <= :intervalEnd :projectInstanceCondition
                ) AS count
            FROM date_sequence ds
        ),
        # lifetime gross revenue
        lt_gross AS (
            SELECT
                DATE_FORMAT(ds.date, ':dateFormat') AS date,
                (
                    SELECT SUM(stats.gross)
                    FROM stats_payments stats
                    :trackingJoinConditions
                    WHERE stats.time <= :intervalEnd :projectInstanceCondition
                ) AS count
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
            COALESCE(lt_net.count, 0) AS lt_NET,
            COALESCE(lt_gross.count, 0) AS lt_GROSS
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
            LEFT JOIN lt_gross ON DATE_FORMAT(ds.date, ':dateFormat') = lt_gross.date
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
            lt_net.count,
            lt_gross.count
        ORDER BY
            DATE_FORMAT(ds.date, ':dateFormat') DESC;
        ";

        // endDate may be a month x.
        // endDateStart will be the start of the endDate month (e.g., 2024-01-01), and
        // endDateEnd will be the end of the endDate month (e.g., 2024-01-31)
        $sql = str_replace([
                ':startDateStart',
                ':endDateStart',
                ':endDateEnd',
                ':intervalType',
                ':intervalEnd',
                ':dateFormat',
                ':projectInstanceCondition',
                ':trackingConditions',
                ':trackingJoinConditions',
            ],
            $this->getFilterConditions($filters),
            $sql
        );

        return DB::select($sql);
    }

    private function getFilterConditions(StatsRangeFilterDto $filters): array
    {
        $conditions = StatsRangeType::conditions($filters->rangeStart, $filters->rangeAmount)[$filters->rangeType->value];

        // add team_id is very important
        $projectInstanceCondition = " AND stats.team_id = ".auth()->user()->current_team_id." ";
        if (!empty($filters->projectId)) {
            $projectInstanceCondition .= " AND stats.project_id = {$filters->projectId} ";
            if (!empty($filters->instanceId)) {
                $projectInstanceCondition .= " AND stats.instance_id = {$filters->instanceId} ";
            }
        }

        $conditions[] = $projectInstanceCondition;

        $trackingCondition = "";

        if (!empty($filters->trackSource)) {
            $trackSourceCondition = ($filters->trackSource === StatsRangeFilterDto::TRACK_ORGANIC) ? " IS NULL " : " = {$filters->trackSource} ";
            $trackingCondition .= " AND stats.stats_track_source_id $trackSourceCondition ";
        }

        if (!empty($filters->trackMedium) && $filters->trackSource !== StatsRangeFilterDto::TRACK_ORGANIC)      $trackingCondition .= " AND stats.stats_track_medium_id = {$filters->trackMedium} ";
        if (!empty($filters->trackCampaign) && $filters->trackSource !== StatsRangeFilterDto::TRACK_ORGANIC)    $trackingCondition .= " AND stats.stats_track_campaign_id = {$filters->trackCampaign} ";
        if (!empty($filters->trackTerm) && $filters->trackSource !== StatsRangeFilterDto::TRACK_ORGANIC)        $trackingCondition .= " AND stats.stats_track_term_id = {$filters->trackTerm} ";
        if (!empty($filters->trackContent) && $filters->trackSource !== StatsRangeFilterDto::TRACK_ORGANIC)     $trackingCondition .= " AND stats.stats_track_content_id = {$filters->trackContent} ";

        if (!empty($trackingCondition)) {
            $conditions[] = $trackingCondition;
            $trackingJoinCondition = str_replace('stats.', 'su.', $trackingCondition);
            $conditions[] = "RIGHT JOIN stats_users su ON su.id = stats.stats_user_id AND su.instance_id = stats.instance_id $trackingJoinCondition";
        }

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

    private function applyStatsTotal(array &$stats): void
    {
        $statsTotal = [];

        foreach ($stats as $stat) {
            $this->statsService->calcStatsTotal(
                stats: $stat['stats'],
                totalStats: $statsTotal,
                skipCategories: ['lifetime']
            );
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
        return collect(StatsRangeType::values())->transform(fn($name, $key) => [
            'id' => $name, 'name' => ucfirst($name)
        ]);
    }

    public function getAllFilterFor(string $type): array
    {
        return ['id' => StatsRangeFilterDto::TRACK_ALL, 'name' => 'All'];//.$type];
    }

    public function getOrganicFilter(): array
    {
        return ['id' => StatsRangeFilterDto::TRACK_ORGANIC, 'name' => 'Organic traffic'];
    }
}
