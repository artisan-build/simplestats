<?php

namespace App\Services;

use App\Builder\StatsAggregationsGroupedSqlBuilder;
use App\Builder\StatsAggregationsSqlBuilder;
use App\DTOs\StatsFilterDto;
use App\Enums\StatsRangeType;
use App\Enums\TrackablePerson;
use App\Models\BaseStat;
use App\Models\Instance;
use App\Models\Project;
use App\Models\StatsAggregation;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Carbon\CarbonPeriodImmutable;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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

    /**
     * convert the objects to arrays for consistency
     *
     * @param  string  $sql
     * @return array
     */
    protected function getDataArray(string $sql): array
    {
        return json_decode(json_encode(DB::select($sql)), true);
    }

    /**
     * TODO the $limit should actually affect the query, but I want to filter by payments, regs, maus
     */
    public function getAggregatedGroupedData(StatsFilterDto $filters, string $statsType, string $statsSort = 'net', int $limit = 9): Collection
    {
        $statsTypeSnake = str($statsType)->snake()->toString();

        $statsDBType = 'stats_'.$statsTypeSnake.'_id';
        $statsTable = "stats_".str($statsTypeSnake)->plural()->toString();

        // for track not but for referer we also want to know the Direct / Unknown
        $typeIdFallback =  (! str($statsType)->startsWith('track') || $statsType === 'trackReferer') ? '-1' : 'NULL';

        $trackablePerson = $this->getTrackablePersonByProject(Project::find($filters->projectId));

        $sql = (new StatsAggregationsGroupedSqlBuilder(
            $statsTable, $statsDBType, $statsSort, $typeIdFallback, $trackablePerson)
        )->build();

        $filterConditions = $this->getFilterConditions($filters);

        if ($filters->lifetime) {
            $filterConditions['startDateStart'] = '1970-01-01';
        }

        if ($trackablePerson === TrackablePerson::USER) {
            $dauDates = $filters->rangeType->getDAUDates(Carbon::parse($filterConditions['endDateStart']));

            if (empty($dauDates)) {
                $filterConditions['dauEndDateStart'] = $filterConditions['endDateStart'];
                $filterConditions['dauEndDateEnd'] = $filterConditions['endDateEnd'];
            } else {
                $filterConditions['dauEndDateStart'] = $dauDates['start'];
                $filterConditions['dauEndDateEnd'] = $dauDates['end'];
            }
        }

        $sql = strtr($sql, $this->formatFilter($filterConditions));
//        echo nl2br($sql);die;

        $dataArray = $this->getDataArray($sql);

        return collect($dataArray)->reject(function ($item) {
            return (! isset($item['dau']) || $item['dau'] == 0)
                && (! isset($item['reg']) || $item['reg'] == 0)
                && (! isset($item['net']) || $item['net'] == 0)
                && (! isset($item['visitors']) || $item['visitors'] == 0);
        })->take($limit);
    }

    private function formatAggregatedData(Collection $aggregatedData): Collection
    {
        return $aggregatedData->keyBy('date_clear');
    }

    public function getAggregatedData(StatsFilterDto $filters): Collection
    {
        $aggregatedData = collect();
        $filterConditions = $this->getFilterConditions($filters);

        if (config('app.stats_aggregation_enabled')) {
            $aggregatedDataKeyedById = StatsAggregation::whereBetween('time', [
                $filterConditions['startDateStart'],
                $filterConditions['endDateEnd']
            ])->where('filter_hash', $filters->makeHash())
                ->orderBy('time', 'desc')
                ->pluck('data', 'id');

            // we need to update this information, so that we can delete aggregations which are older than x days...
            StatsAggregation::whereIn('id', $aggregatedDataKeyedById->keys())->update(['selected_at' => now()]);

            $aggregatedData = $this->formatAggregatedData($aggregatedDataKeyedById);
        }

        if ($aggregatedData->isEmpty()) {
            $aggregatedData = $this->getRawData($filterConditions, $filters->projectId);
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

        $teamCondition = " stats.team_id = ".auth()->user()->current_team_id." ";

        $projectCondition = "";
        if (!empty($filters->projectId)) {
            $projectCondition .= " AND stats.project_id = {$filters->projectId} ";
        }

        $projectInstanceCondition = "";
        if (!empty($filters->instanceId)) {
            $projectInstanceCondition .= " AND stats.instance_id = {$filters->instanceId} ";
        } elseif (!empty($filters->projectId)) {
            $projectInstanceCondition .= $projectCondition;
        }

        $conditions['projectCondition'] = $teamCondition.$projectCondition;
        $conditions['projectInstanceCondition'] = $teamCondition.$projectInstanceCondition;

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

        $conditions['trackingConditions'] = "";
        $conditions['trackingUserJoinConditions'] = "RIGHT JOIN stats_users su ON su.id = stats.stats_user_id AND su.instance_id = stats.instance_id";
        $conditions['trackingVisitorJoinConditions'] = "RIGHT JOIN stats_visitors sv ON sv.id = stats.stats_visitor_id AND sv.instance_id = stats.instance_id";

        if (!empty($trackingCondition)) {
            $conditions['trackingConditions'] = $trackingCondition;
            $conditions['trackingUserJoinConditions'] .= ' ' . str_replace('stats.', 'su.', $trackingCondition);
            $conditions['trackingVisitorJoinConditions'] .= ' ' . str_replace('stats.', 'sv.', $trackingCondition);
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

            $missingAggregatedData = $missingAggregatedData->merge($this->getRawData($this->getFilterConditions($filtersBefore), $filtersBefore->projectId));
        }

        if (! empty($missingAfterPeriod)) {
            // modify the original filter and adjust range amount and start
            $filtersAfter = new StatsFilterDto(...array_merge((array) $filters, [
                'rangeAmount' => count($missingAfterPeriod),
                'rangeStart' => current($missingAfterPeriod),
            ]));

            $missingAggregatedData = $missingAggregatedData->merge($this->getRawData($this->getFilterConditions($filtersAfter), $filtersAfter->projectId));
        }

        return $missingAggregatedData;
    }

    public function getRawData(array $filterConditions, ?int $projectId): Collection
    {
        $trackablePerson = $this->getTrackablePersonByProject(Project::find($projectId));

        $sql = strtr(
            (new StatsAggregationsSqlBuilder($trackablePerson))->build(),
            $this->formatFilter($filterConditions)
        );
        echo nl2br($sql);die;

        $dataArray = $this->getDataArray($sql);
        $this->applyContinuousLTAggregation($dataArray);
//dd($dataArray);
        return $this->formatAggregatedData(collect($dataArray));
    }

    private function applyContinuousLTAggregation(array &$dataArray): void
    {
        $dataArray = array_reverse($dataArray);
//dd($dataArray);
        $i = 0;
        foreach ($dataArray as $item) {
            foreach ($item as $key => $value) {
                if (Str::startsWith($key, 'lt_')) {
                    $pdKey = Str::replaceFirst('lt_', 'pd_', $key);
                    $dataArray[$i][$key] += $dataArray[$i][$pdKey];

                    $nextI = $i + 1;
                    if (isset($dataArray[$nextI])) {
                        $dataArray[$nextI][$key] = $dataArray[$i][$key];
                    }
                }
            }

            $i++;
        }

        $dataArray = array_reverse($dataArray);
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
            $start = $case->rangeStart($stat->time)->format($case->dateFormatClear()['php']);

            StatsAggregation::where([
                ['time', '>=', $start],
                ['type', '=', $case->value],
            ])->where(function ($query) use ($stat) {
                // stats must be re-aggregated on team basis
                $query->where(function ($query) use ($stat) {
                    $query->where('team_id', '=', $stat->team_id)->where('project_id', '=', null)->where('instance_id', '=', null);
                })
                // stats must be re-aggregated on project basis
                ->orWhere(function ($query) use ($stat) {
                    $query->where('team_id', '=', $stat->team_id)->where('project_id', '=', $stat->project_id)->where('instance_id', '=', null);
                })
                // stats must be re-aggregated on instance basis
                ->orWhere(function ($query) use ($stat) {
                    $query->where('team_id', '=', $stat->team_id)->where('project_id', '=', $stat->project_id)->where('instance_id', '=', $stat->instance_id);
                });
            })->delete();
            // other projects and instances aggregations will remain
            // but unfortunately we have to re-aggregate the whole team
        }
    }

    public function clearByInstance(Instance $instance): void
    {
        StatsAggregation::where(function ($query) use ($instance) {
            // stats must be re-aggregated on team basis
            $query->where(function ($query) use ($instance) {
                $query->where('team_id', '=', $instance->team_id)->where('project_id', '=', null)->where('instance_id', '=', null);
            })
            // stats must be re-aggregated on project basis
            ->orWhere(function ($query) use ($instance) {
                $query->where('team_id', '=', $instance->team_id)->where('project_id', '=', $instance->project_id)->where('instance_id', '=', null);
            })
            // stats must be re-aggregated on instance basis
            ->orWhere(function ($query) use ($instance) {
                $query->where('team_id', '=', $instance->team_id)->where('project_id', '=', $instance->project_id)->where('instance_id', '=', $instance->id);
            });
        })->delete();
    }

    public function clearByProject(Project $project): void
    {
        StatsAggregation::where(function ($query) use ($project) {
            // stats must be re-aggregated on team basis
            $query->where(function ($query) use ($project) {
                $query->where('team_id', '=', $project->team_id)->where('project_id', '=', null)->where('instance_id', '=', null);
            })
            // stats must be re-aggregated on project basis
            ->orWhere(function ($query) use ($project) {
                $query->where('team_id', '=', $project->team_id)->where('project_id', '=', $project->id)->where('instance_id', '=', null);
            });
        })->delete();
    }

    private function formatFilter(array $filterConditions): array
    {
        return collect($filterConditions)->mapWithKeys(function ($value, $key) {
            return [':'.$key => $value];
        })->toArray();
    }

    private function getTrackablePersonByProject(?Project $project): TrackablePerson
    {
        $trackablePerson = TrackablePerson::VISITOR;

        if (! $project || $project->hasTrackingUsageBits([StatsService::TRACKING_USAGE_BITS_USERS])) {
            $trackablePerson = TrackablePerson::USER;
        }

        return $trackablePerson;
    }
}
