<?php

namespace App\Services;

use App\DTOs\StatsRangeFilterDto;
use App\Enums\StatsRangeType;
use App\Models\BaseStat;
use App\Models\StatsAggregation;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Carbon\CarbonPeriod;
use Carbon\CarbonPeriodImmutable;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class StatsAggregationsService
{
    public function storeAggregatedData(Collection $data, StatsRangeFilterDto $filters): void
    {
        $upsertData = $data->map(function ($item) use ($filters) {
            return [
                'team_id' => $filters->getTeamId(),
                'project_id' => $filters->projectId ?: null,
                'instance_id' => $filters->instanceId ?: null,
                'filter_hash' => $filters->makeHash(),
                'type' => $filters->rangeType,
                'time' => $item['date_clear'],
                'data' => json_encode($item),
                'selected_at' => now(),
            ];
        });

        StatsAggregation::upsert($upsertData->toArray(), uniqueBy: ['filter_hash', 'time'], update: ['data']);
    }

    private function formatAggregatedData(Collection $aggregatedData): Collection
    {
        return $aggregatedData->keyBy('date_clear');
    }

    public function getAggregatedData(StatsRangeFilterDto $filters): Collection
    {
        $filterConditions = $this->getFilterConditions($filters);

        $aggregatedDataKeyedById = StatsAggregation::whereBetween('time', [
            $filterConditions['startDateStart'],
            $filterConditions['endDateEnd']
        ])->where('filter_hash', $filters->makeHash())
            ->orderBy('time', 'desc')
            ->pluck('data', 'id');

        // we need to update this information, so that we can delete aggregations which are older than x days...
        StatsAggregation::whereIn('id', $aggregatedDataKeyedById->keys())->update(['selected_at' => now()]);

        $aggregatedData = $this->formatAggregatedData($aggregatedDataKeyedById);

        if ($aggregatedData->isEmpty()) {
            $aggregatedData = $this->getRawData($filterConditions);
            $this->storeAggregatedData($aggregatedData, $filters);
        } else {
            $missingAggregatedData = $this->getMissingAggregatedData($aggregatedData, $filters);

            if ($missingAggregatedData->isNotEmpty()) {
                $aggregatedData = $aggregatedData->merge($missingAggregatedData);
                $this->storeAggregatedData($missingAggregatedData, $filters);
            }
        }

        return $aggregatedData->sortKeysDesc();
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

        $conditions['projectInstanceCondition'] = $projectInstanceCondition;

        $trackingCondition = "";

        if (!empty($filters->trackCountry)) {
            $trackCountryCondition = ($filters->trackCountry === StatsRangeFilterDto::TRACK_ORGANIC) ? " IS NULL " : " = {$filters->trackCountry} ";
            $trackingCondition .= " AND stats.stats_track_country_id $trackCountryCondition ";
        }

        if (!empty($filters->trackSource)) {
            $trackSourceCondition = ($filters->trackSource === StatsRangeFilterDto::TRACK_ORGANIC) ? " IS NULL " : " = {$filters->trackSource} ";
            $trackingCondition .= " AND stats.stats_track_source_id $trackSourceCondition ";
        }

        if (!empty($filters->trackMedium) && $filters->trackSource !== StatsRangeFilterDto::TRACK_ORGANIC)      $trackingCondition .= " AND stats.stats_track_medium_id = {$filters->trackMedium} ";
        if (!empty($filters->trackCampaign) && $filters->trackSource !== StatsRangeFilterDto::TRACK_ORGANIC)    $trackingCondition .= " AND stats.stats_track_campaign_id = {$filters->trackCampaign} ";
        if (!empty($filters->trackTerm) && $filters->trackSource !== StatsRangeFilterDto::TRACK_ORGANIC)        $trackingCondition .= " AND stats.stats_track_term_id = {$filters->trackTerm} ";
        if (!empty($filters->trackContent) && $filters->trackSource !== StatsRangeFilterDto::TRACK_ORGANIC)     $trackingCondition .= " AND stats.stats_track_content_id = {$filters->trackContent} ";

        if (!empty($trackingCondition)) {
            $conditions['trackingConditions'] = $trackingCondition;
            $trackingJoinCondition = str_replace('stats.', 'su.', $trackingCondition);
            $conditions['trackingJoinConditions'] = "RIGHT JOIN stats_users su ON su.id = stats.stats_user_id AND su.instance_id = stats.instance_id $trackingJoinCondition";
        }

        return $conditions;
    }

    /**
     * We consider every data above a missing date as compromised.
     * If data changes in retrospective (e.g., when a payment state gets changed or updated),
     * every data above may not be properly evaluated. So we "getRawData" of all data above the date.
     *
     * @param  Collection  $aggregatedData
     * @param  StatsRangeFilterDto  $filters
     * @return Collection
     */
    private function getMissingAggregatedData(Collection $aggregatedData, StatsRangeFilterDto $filters): Collection
    {
        $filterConditions = $this->getFilterConditions($filters);

        $period = array_reverse(CarbonPeriodImmutable::create(
            $filterConditions['startDateStart'],
            '1 '.$filterConditions['intervalType']->value,
            $filterConditions['endDateStart']
        )->toArray());

        $aggregatedDataSorted = $aggregatedData->sortKeys();

        $startDate = $aggregatedDataSorted->keys()->first();
        $endDate = $aggregatedDataSorted->keys()->last();

        $missingBeforePeriod = [];
        $missingAfterPeriod = [];

        foreach ($period as $date) {
            $date = CarbonImmutable::parse($date);

            if ($date->lt($startDate)) {
                $missingAfterPeriod[] = $date;
            } elseif ($date->gt($endDate)) {
                $missingBeforePeriod[] = $date;
            }
        }

        $missingAggregatedData = collect();

        if (! empty($missingBeforePeriod)) {
            // modify the original filter and adjust range amount and start
            $filtersBefore = new StatsRangeFilterDto(...array_merge((array) $filters, [
                'rangeAmount' => count($missingBeforePeriod),
                'rangeStart' => current($missingBeforePeriod),
            ]));

            $missingAggregatedData = $missingAggregatedData->merge($this->getRawData($this->getFilterConditions($filtersBefore)));
        }

        if (! empty($missingAfterPeriod)) {
            // modify the original filter and adjust range amount and start
            $filtersAfter = new StatsRangeFilterDto(...array_merge((array) $filters, [
                'rangeAmount' => count($missingAfterPeriod),
                'rangeStart' => current($missingAfterPeriod),
            ]));

            $missingAggregatedData = $missingAggregatedData->merge($this->getRawData($this->getFilterConditions($filtersAfter)));
        }

        return $missingAggregatedData;
    }

    public function getRawData(array $filterConditions): Collection
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
            DATE_FORMAT(ds.date, '%Y-%m-%d') as date_clear,
            COALESCE(pd_reg.count, 0) AS pd_REG,
            COALESCE(pd_nau.count, 0) AS pd_NAU,
            COALESCE(pd_napu.count, 0) AS pd_NAPU,
            COALESCE(pd_dau.count, 0) AS pd_DAU,
            COALESCE(pd_dapu.count, 0) AS pd_DAPU,
            COALESCE(pd_daapu.count, 0) AS pd_DAAPU,
            COALESCE(ROUND(pd_gross.count,2), 0) AS pd_GROSS,
            COALESCE(ROUND(pd_net.count,2), 0) AS pd_NET,
            COALESCE(lt_reg.count, 0) AS lt_REG,
            COALESCE(lt_pu.count, 0) AS lt_PU,
            COALESCE(lt_tau.count, 0) AS lt_TAU,
            COALESCE(ROUND(lt_net.count,2), 0) AS lt_NET,
            COALESCE(ROUND(lt_gross.count,2), 0) AS lt_GROSS
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
            DATE_FORMAT(ds.date, '%Y-%m-%d'),
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
            $filterConditions,
            $sql
        );

        // convert the objects to arrays for consistency
        $array = json_decode(json_encode(DB::select($sql)), true);
        return $this->formatAggregatedData(collect($array));
    }

    /**
     * If a stat updates, every data above the stat time is invalid, so we need to delete it.
     * Same counts for data-imports.
     *
     * @param  BaseStat  $stat
     * @return void
     * @throws Exception
     */
    public function invalidateByStatsUpdate(BaseStat $stat): void
    {
        foreach (StatsRangeType::cases() as $case) {
            // it's important to convert the time to the corresponding range types (days, weeks, months)
            // for instance: if the update time is 2024-05-15 you'll also want to delete form its corresponding week,
            // which starts at 2024-05-13 (same counts for months)
            $start = $case->rangeStart($stat->time)->format(StatsRangeType::DATE_FORMAT);

            StatsAggregation::where([
                ['time', '>=', $start],
                ['type', '=', $case->value],
            ])->where(function ($query) use ($stat) {
                // stats must be re-aggregated on team basis
                $query->where([
                    ['team_id', '=', $stat->team_id],
                    ['project_id', '=', null],
                    ['instance_id', '=', null],
                // stats must be re-aggregated on project basis
                ])->orWhere([
                    ['team_id', '=', $stat->team_id],
                    ['project_id', '=', $stat->project_id],
                    ['instance_id', '=', null],
                // stats must be re-aggregated on instance basis
                ])->orWhere([
                    ['team_id', '=', $stat->team_id],
                    ['project_id', '=', $stat->project_id],
                    ['instance_id', '=', $stat->instance_id],
                ]);
            })->delete();
            // other projects and instances aggregations will remain
            // but unfortunately we have to re-aggregate the whole team
        }
    }
}
