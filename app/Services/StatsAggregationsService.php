<?php

namespace App\Services;

use App\DTOs\StatsFilterDto;
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
    public function storeAggregatedData(Collection $data, StatsFilterDto $filters): void
    {
        if (! config('app.stats_aggregation_enabled'))
            return;

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

    public function getAggregatedGroupedData(StatsFilterDto $filters, string $statsType, string $statsSort = 'net', int $limit = 9): Collection
    {
        // TODO the $limit should actually affect the query, but I want to filter by payments, regs, maus

        $nauLoginCounter = StatsService::NAU_LOGIN_COUNTER;
        $statsTypeSnake = str($statsType)->snake()->toString();
        $statsDBType = 'stats_'.$statsTypeSnake.'_id';

        $statsTable = "stats_".str($statsTypeSnake)->plural()->toString();

        // for track not but for referer we also want to know the Direct / Unknown
        $typeIdFallback =  (! str($statsType)->startsWith('track') || $statsType === 'trackReferer') ? '-1' : 'NULL';

        // TODO just a simple join from the $statsTable?

        $sql = "
        WITH stats_groups AS (
            SELECT id as type_id, name FROM $statsTable stats WHERE 1 :projectCondition
            UNION ALL
            SELECT $typeIdFallback as type_id, 'Direct / Unknown' as name
        ),
        # dau: Number of unique accounts who logged in on this day (without 1st login = registration)
        pd_dau AS (
            SELECT COALESCE(su.$statsDBType, $typeIdFallback) as type_id, COUNT(DISTINCT stats.stats_user_id, stats.instance_id) AS count
            FROM stats_logins stats
            :trackingJoinConditions :projectInstanceCondition
            WHERE stats.time >= ':dauEndDateStart' AND stats.time <= ':dauEndDateEnd' and stats.counter >= $nauLoginCounter :projectInstanceCondition
            GROUP BY su.$statsDBType
        ),
        # lifetime visitpors
        lt_visitors AS (
            SELECT COALESCE(stats.$statsDBType, $typeIdFallback) as type_id, COUNT(DISTINCT stats.id, stats.instance_id) as count
            FROM stats_visitors stats
            WHERE stats.time >= ':startDateStart' AND stats.time <= ':endDateEnd'
            :trackingConditions :projectInstanceCondition
            GROUP BY stats.$statsDBType
        ),
        # lifetime regs
        lt_reg AS (
            SELECT COALESCE(stats.$statsDBType, $typeIdFallback) as type_id, COUNT(DISTINCT stats.id, stats.instance_id) as count
            FROM stats_users stats
            WHERE stats.time >= ':startDateStart' AND stats.time <= ':endDateEnd'
            :trackingConditions :projectInstanceCondition
            GROUP BY stats.$statsDBType
        ),
        # lifetime net revenue
        lt_net AS (
            SELECT COALESCE(su.$statsDBType, $typeIdFallback) as type_id, SUM(stats.net) as count
            FROM stats_payments stats
            :trackingJoinConditions :projectInstanceCondition
            WHERE stats.time >= ':startDateStart' AND stats.time <= ':endDateEnd' :projectInstanceCondition
            GROUP BY su.$statsDBType
        )
        SELECT
            sg.type_id as type_id,
            sg.name as name,
            COALESCE(lt_visitors.count, 0) AS visitors,
            COALESCE(lt_reg.count, 0) AS reg,
            COALESCE(lt_reg.count / lt_visitors.count * 100, 0) AS cr,
            COALESCE(pd_dau.count, 0) AS dau,
            COALESCE(ROUND(lt_net.count,2), 0) AS net
        FROM
            stats_groups sg
            LEFT JOIN pd_dau ON sg.type_id = pd_dau.type_id
            LEFT JOIN lt_reg ON sg.type_id = lt_reg.type_id
            LEFT JOIN lt_visitors ON sg.type_id = lt_visitors.type_id
            LEFT JOIN lt_net ON sg.type_id = lt_net.type_id
        ORDER BY
            $statsSort DESC;
        ";

        $filterConditions = $this->getFilterConditions($filters);

        if ($filters->lifetime) {
            $filterConditions['startDateStart'] = '1970-01-01';
        }

        $endDateStart = $filterConditions['endDateStart'];

        $dauDates = match($filters->rangeType->value) {
            'days' => Carbon::parse($endDateStart)->greaterThanOrEqualTo(now()->startOfDay()) ? [
                'start' => now()->subDay()->startOfDay()->format(StatsRangeType::DATE_FORMAT),
                'end' => now()->subDay()->endOfDay()->format(StatsRangeType::DATE_FORMAT),
            ] : [],
            'weeks' => Carbon::parse($endDateStart)->greaterThanOrEqualTo(now()->startOfWeek()) ? [
                'start' => now()->subWeek()->startOfWeek()->format(StatsRangeType::DATE_FORMAT),
                'end' => now()->subWeek()->endOfWeek()->format(StatsRangeType::DATE_FORMAT),
            ] : [],
            'months' => Carbon::parse($endDateStart)->greaterThanOrEqualTo(now()->startOfMonth()) ? [
                'start' => now()->subMonth()->startOfMonth()->format(StatsRangeType::DATE_FORMAT),
                'end' => now()->subMonth()->endOfMonth()->format(StatsRangeType::DATE_FORMAT),
            ] : [],
            'years' => Carbon::parse($endDateStart)->greaterThanOrEqualTo(now()->startOfYear()) ? [
                'start' => now()->subYear()->startOfYear()->format(StatsRangeType::DATE_FORMAT),
                'end' => now()->subYear()->endOfYear()->format(StatsRangeType::DATE_FORMAT),
            ] : [],
        };

        if (empty($dauDates)) {
            $filterConditions['dauEndDateStart'] = $filterConditions['endDateStart'];
            $filterConditions['dauEndDateEnd'] = $filterConditions['endDateEnd'];
        } else {
            $filterConditions['dauEndDateStart'] = $dauDates['start'];
            $filterConditions['dauEndDateEnd'] = $dauDates['end'];
        }

        if (empty($filterConditions['trackingJoinConditions'])) {
            $filterConditions['trackingJoinConditions'] = "LEFT JOIN stats_users su ON su.id = stats.stats_user_id AND su.instance_id = stats.instance_id";
        }

        $sql = strtr($sql, $this->formatFilter($filterConditions));
        //echo nl2br($sql);die;
        // convert the objects to arrays for consistency
        $dataArray = json_decode(json_encode(DB::select($sql)), true);

        return collect($dataArray)->reject(function ($item) {
            return $item['dau'] == 0 && $item['reg'] == 0 && $item['net'] == 0 && $item['visitors'] == 0;
        })->take($limit);
    }

    public function getAggregatedData(StatsFilterDto $filters): Collection
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

    private function getFilterConditions(StatsFilterDto $filters): array
    {
        $conditions = StatsRangeType::conditions($filters->rangeStart, $filters->rangeAmount)[$filters->rangeType->value];

        // add team_id is very important
        $projectCondition = " AND stats.team_id = ".auth()->user()->current_team_id." ";
        $projectInstanceCondition = $projectCondition;
        if (!empty($filters->projectId)) {
            $projectCondition .= " AND stats.project_id = {$filters->projectId} ";
            $projectInstanceCondition .= $projectCondition;
            if (!empty($filters->instanceId)) {
                $projectInstanceCondition .= " AND stats.instance_id = {$filters->instanceId} ";
            }
        }

        $conditions['projectCondition'] = $projectCondition;
        $conditions['projectInstanceCondition'] = $projectInstanceCondition;

        $trackingCondition = "";

        if (!empty($filters->locationCountry)) {
            $locationCountryCondition = ($filters->locationCountry === StatsFilterDto::TRACK_ORGANIC) ? " IS NULL " : " = {$filters->locationCountry} ";
            $trackingCondition .= " AND stats.stats_location_country_id $locationCountryCondition ";
        }

        if (!empty($filters->locationRegion)) {
            $locationRegionCondition = ($filters->locationRegion === StatsFilterDto::TRACK_ORGANIC) ? " IS NULL " : " = {$filters->locationRegion} ";
            $trackingCondition .= " AND stats.stats_location_region_id $locationRegionCondition ";
        }

        if (!empty($filters->locationCity)) {
            $locationCityCondition = ($filters->locationCity === StatsFilterDto::TRACK_ORGANIC) ? " IS NULL " : " = {$filters->locationCity} ";
            $trackingCondition .= " AND stats.stats_location_city_id $locationCityCondition ";
        }

        if (!empty($filters->deviceType)) {
            $deviceTypeCondition = ($filters->deviceType === StatsFilterDto::TRACK_ORGANIC) ? " IS NULL " : " = {$filters->deviceType} ";
            $trackingCondition .= " AND stats.stats_device_type_id $deviceTypeCondition ";
        }

        if (!empty($filters->devicePlatform)) {
            $devicePlatformCondition = ($filters->devicePlatform === StatsFilterDto::TRACK_ORGANIC) ? " IS NULL " : " = {$filters->devicePlatform} ";
            $trackingCondition .= " AND stats.stats_device_platform_id $devicePlatformCondition ";
        }

        if (!empty($filters->deviceBrowser)) {
            $deviceBrowserCondition = ($filters->deviceBrowser === StatsFilterDto::TRACK_ORGANIC) ? " IS NULL " : " = {$filters->deviceBrowser} ";
            $trackingCondition .= " AND stats.stats_device_browser_id $deviceBrowserCondition ";
        }

        if (!empty($filters->trackReferer)) {
            $trackRefererCondition = ($filters->trackReferer === StatsFilterDto::TRACK_ORGANIC) ? " IS NULL " : " = {$filters->trackReferer} ";
            $trackingCondition .= " AND stats.stats_track_referer_id $trackRefererCondition ";
        }

        if (!empty($filters->trackSource)) {
            $trackSourceCondition = ($filters->trackSource === StatsFilterDto::TRACK_ORGANIC) ? " IS NULL " : " = {$filters->trackSource} ";
            $trackingCondition .= " AND stats.stats_track_source_id $trackSourceCondition ";
        }

        if (!empty($filters->trackMedium) && $filters->trackSource !== StatsFilterDto::TRACK_ORGANIC)      $trackingCondition .= " AND stats.stats_track_medium_id = {$filters->trackMedium} ";
        if (!empty($filters->trackCampaign) && $filters->trackSource !== StatsFilterDto::TRACK_ORGANIC)    $trackingCondition .= " AND stats.stats_track_campaign_id = {$filters->trackCampaign} ";
        if (!empty($filters->trackTerm) && $filters->trackSource !== StatsFilterDto::TRACK_ORGANIC)        $trackingCondition .= " AND stats.stats_track_term_id = {$filters->trackTerm} ";
        if (!empty($filters->trackContent) && $filters->trackSource !== StatsFilterDto::TRACK_ORGANIC)     $trackingCondition .= " AND stats.stats_track_content_id = {$filters->trackContent} ";

        if (!empty($filters->pageEntry)) {
            $pageEntryCondition = ($filters->pageEntry === StatsFilterDto::TRACK_ORGANIC) ? " IS NULL " : " = {$filters->pageEntry} ";
            $trackingCondition .= " AND stats.stats_page_entry_id $pageEntryCondition ";
        }

        if (!empty($trackingCondition)) {
            $conditions['trackingConditions'] = $trackingCondition;
            $trackingJoinCondition = str_replace('stats.', 'su.', $trackingCondition);
            $conditions['trackingJoinConditions'] = "RIGHT JOIN stats_users su ON su.id = stats.stats_user_id AND su.instance_id = stats.instance_id $trackingJoinCondition";
        } else {
            $conditions['trackingConditions'] = "";
            $conditions['trackingJoinConditions'] = "";
        }

        return $conditions;
    }

    /**
     * We consider every data above a missing date as compromised.
     * If data changes in retrospective (e.g., when a payment state gets changed or updated),
     * every data above may not be properly evaluated. So we "getRawData" of all data above the date.
     *
     * @param  Collection  $aggregatedData
     * @param  StatsFilterDto  $filters
     * @return Collection
     */
    private function getMissingAggregatedData(Collection $aggregatedData, StatsFilterDto $filters): Collection
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
            $filtersBefore = new StatsFilterDto(...array_merge((array) $filters, [
                'rangeAmount' => count($missingBeforePeriod),
                'rangeStart' => current($missingBeforePeriod),
            ]));

            $missingAggregatedData = $missingAggregatedData->merge($this->getRawData($this->getFilterConditions($filtersBefore)));
        }

        if (! empty($missingAfterPeriod)) {
            // modify the original filter and adjust range amount and start
            $filtersAfter = new StatsFilterDto(...array_merge((array) $filters, [
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
        pd_visitor AS (
            SELECT DATE_FORMAT(stats.time, ':dateFormat') AS date, COUNT(DISTINCT stats.id, stats.instance_id) as count
            FROM stats_visitors stats
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
            WHERE stats.time >= ':startDateStart' AND stats.time <= ':endDateEnd' and stats.counter >= $nauLoginCounter :projectInstanceCondition
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
        # pu: Number of unique accounts who did at least one payment over lifetime (including today)
        pd_pu AS (
            SELECT DATE_FORMAT(stats.time, ':dateFormat') AS date, COUNT(DISTINCT stats.stats_user_id, stats.instance_id) AS count
            FROM stats_logins stats
            :trackingJoinConditions
            WHERE EXISTS (
                SELECT 1
                FROM stats_payments sp
                WHERE stats.stats_user_id = sp.stats_user_id
                    AND stats.instance_id = sp.instance_id
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
        # lifetime visitors
        lt_visitor AS (
            SELECT
                DATE_FORMAT(ds.date, ':dateFormat') AS date,
                (
                    SELECT COUNT(DISTINCT stats.id, stats.instance_id)
                    FROM stats_visitors stats
                    WHERE stats.time <= :intervalEnd :projectInstanceCondition :trackingConditions
                ) AS count
            FROM date_sequence ds
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
            COALESCE(pd_visitor.count, 0) AS pd_VISITOR,
            COALESCE(pd_nau.count, 0) AS pd_NAU,
            COALESCE(pd_napu.count, 0) AS pd_NAPU,
            COALESCE(pd_dau.count, 0) AS pd_DAU,
            COALESCE(pd_dapu.count, 0) AS pd_DAPU,
            COALESCE(pd_daapu.count, 0) AS pd_DAAPU,
            COALESCE(ROUND(pd_gross.count,2), 0) AS pd_GROSS,
            COALESCE(ROUND(pd_net.count,2), 0) AS pd_NET,
            COALESCE(lt_visitor.count, 0) AS lt_VISITOR,
            COALESCE(lt_reg.count, 0) AS lt_REG,
            COALESCE(pd_pu.count, 0) AS pd_PU,
            COALESCE(lt_tau.count, 0) AS lt_TAU,
            COALESCE(ROUND(lt_net.count,2), 0) AS lt_NET,
            COALESCE(ROUND(lt_gross.count,2), 0) AS lt_GROSS
        FROM
            date_sequence ds
            LEFT JOIN pd_reg ON DATE_FORMAT(ds.date, ':dateFormat') = pd_reg.date
            LEFT JOIN pd_visitor ON DATE_FORMAT(ds.date, ':dateFormat') = pd_visitor.date
            LEFT JOIN pd_nau ON DATE_FORMAT(ds.date, ':dateFormat') = pd_nau.date
            LEFT JOIN pd_napu ON DATE_FORMAT(ds.date, ':dateFormat') = pd_napu.date
            LEFT JOIN pd_dau ON DATE_FORMAT(ds.date, ':dateFormat') = pd_dau.date
            LEFT JOIN pd_dapu ON DATE_FORMAT(ds.date, ':dateFormat') = pd_dapu.date
            LEFT JOIN pd_daapu ON DATE_FORMAT(ds.date, ':dateFormat') = pd_daapu.date
            LEFT JOIN pd_gross ON DATE_FORMAT(ds.date, ':dateFormat') = pd_gross.date
            LEFT JOIN pd_net ON DATE_FORMAT(ds.date, ':dateFormat') = pd_net.date
            LEFT JOIN lt_reg ON DATE_FORMAT(ds.date, ':dateFormat') = lt_reg.date
            LEFT JOIN lt_visitor ON DATE_FORMAT(ds.date, ':dateFormat') = lt_visitor.date
            LEFT JOIN pd_pu ON DATE_FORMAT(ds.date, ':dateFormat') = pd_pu.date
            LEFT JOIN lt_tau ON DATE_FORMAT(ds.date, ':dateFormat') = lt_tau.date
            LEFT JOIN lt_net ON DATE_FORMAT(ds.date, ':dateFormat') = lt_net.date
            LEFT JOIN lt_gross ON DATE_FORMAT(ds.date, ':dateFormat') = lt_gross.date
        ORDER BY
            DATE_FORMAT(ds.date, ':dateFormat') DESC;
        ";

        $sql = strtr($sql, $this->formatFilter($filterConditions));
//         echo nl2br($sql);die;
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

    private function formatFilter(array $filterConditions): array
    {
        return collect($filterConditions)->mapWithKeys(function ($value, $key) {
            return [':'.$key => $value];
        })->toArray();
    }
}
