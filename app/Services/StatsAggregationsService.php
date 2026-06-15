<?php

namespace App\Services;

use App\Builder\StatsAggregationsGroupedEventsSqlBuilder;
use App\Builder\StatsAggregationsGroupedPropertiesSqlBuilder;
use App\Builder\StatsAggregationsGroupedSqlBuilder;
use App\Builder\StatsAggregationsSqlBuilder;
use App\DTOs\StatsFilterDto;
use App\Enums\StatsRangeType;
use App\Enums\TrackablePerson;
use App\Helpers\AppHelper;
use App\Models\BaseStat;
use App\Models\Project;
use App\Models\StatsAggregation;
use App\Models\StatsCustomPropertyName;
use App\Models\StatsCustomPropertyValue;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Carbon\CarbonPeriodImmutable;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class StatsAggregationsService
{
    public function storeAggregatedData(Collection $data, StatsFilterDto $filters): void
    {
        if (! config('app.stats_aggregation_enabled')) {
            return;
        }

        $teamId = AppHelper::getTeamId();
        $filterHash = $filters->makeHash();
        $filterJson = json_encode($filters->toFilterArray());

        $upsertData = $data->map(function ($item) use ($filters, $teamId, $filterHash, $filterJson) {
            return [
                'team_id' => $teamId,
                'project_id' => $filters->projectId ?: null,
                'filter_hash' => $filterHash,
                'filter' => $filterJson,
                'type' => $filters->rangeType,
                'time' => $item['date_clear'],
                'data' => json_encode($item),
                'selected_at' => now(),
            ];
        });

        StatsAggregation::upsert($upsertData->toArray(), uniqueBy: ['filter_hash', 'time'], update: ['data', 'filter']);
    }

    /**
     * convert the objects to arrays for consistency
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
        $statsTable = 'stats_'.str($statsTypeSnake)->plural()->toString();

        // for track not but for referer we also want to know the Direct / Unknown
        $typeIdFallback = (! str($statsType)->startsWith('track') || $statsType === 'track_referer') ? '-1' : 'NULL';

        $project = Project::with('team:id,timezone')->find($filters->projectId);
        $trackablePerson = $this->getTrackablePersonByProject($project);

        if ($statsType === 'custom_event_name') {
            $sql = (new StatsAggregationsGroupedEventsSqlBuilder(
                $statsSort)
            )->build();
        } else {
            $sql = (new StatsAggregationsGroupedSqlBuilder(
                $statsTable, $statsDBType, $statsSort, $typeIdFallback, $trackablePerson)
            )->build();
        }

        $filterConditions = $this->getFilterConditions($filters);

        if ($trackablePerson === TrackablePerson::USER) {
            $tz = $project?->team?->timezone ?? config('app.timezone');
            $dauDates = $filters->rangeType->getDAUDates(Carbon::parse($filterConditions['endDateStart'], $tz));

            if (empty($dauDates)) {
                $filterConditions['dauEndDateStart'] = $filterConditions['endDateStart'];
                $filterConditions['dauEndDateEnd'] = $filterConditions['endDateEnd'];
            } else {
                $filterConditions['dauEndDateStart'] = $dauDates['start'];
                $filterConditions['dauEndDateEnd'] = $dauDates['end'];
            }
        }

        $sql = strtr($sql, $this->formatFilter($filterConditions));
        // echo nl2br($sql);die;

        $dataArray = $this->roundNetValues($this->getDataArray($sql));

        // hack to fix rtl stats grouped page names
        foreach ($dataArray as &$item) {
            if (Str::startsWith($item['name'], '/')) {
                $item['name'] = Str::replaceFirst('/', '', $item['name']).'/';
            }
        }

        return collect($dataArray)->reject(function ($item) {
            return (! isset($item['dau']) || $item['dau'] == 0)
                && (! isset($item['reg']) || $item['reg'] == 0)
                && (! isset($item['net']) || $item['net'] == 0)
                && (! isset($item['visitors']) || $item['visitors'] == 0)
                && (! isset($item['triggered']) || $item['triggered'] == 0);
        })->take($limit);
    }

    /**
     * Group stats by the values of a single custom property (e.g. "subscription"
     * bucketed into free / pro / enterprise). Runs as a standalone path next to
     * the static tracking dimensions because the grouping key is dynamic and the
     * data lives in the polymorphic stats_custom_properties pivot.
     *
     * Property lookups are project-scoped: a project view uses only that
     * project's name id, the "All projects" view includes every same-named id
     * of the team and merges the buckets by value string.
     */
    public function getAggregatedPropertyData(StatsFilterDto $filters, int $propertyNameId, string $statsSort = 'reg', int $limit = 9): Collection
    {
        $referenceName = StatsCustomPropertyName::find($propertyNameId);

        if ($referenceName === null) {
            return collect();
        }

        $nameIds = StatsCustomPropertyService::equivalentNameIds($referenceName, $filters->projectId);

        if ($nameIds->isEmpty()) {
            return collect();
        }

        $sql = (new StatsAggregationsGroupedPropertiesSqlBuilder(
            $statsSort
        ))->build();

        $filterConditions = $this->getFilterConditions($filters);
        $filterConditions['propertyNameIds'] = $nameIds->implode(',');

        $sql = strtr($sql, $this->formatFilter($filterConditions));

        $dataArray = $this->roundNetValues($this->getDataArray($sql));

        return collect($dataArray)->reject(function ($item) {
            return $item['reg'] == 0 && $item['visitors'] == 0 && $item['net'] == 0;
        })->take($limit);
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, array<string, mixed>>
     */
    private function roundNetValues(array $rows): array
    {
        return array_map(function ($item) {
            if (isset($item['net'])) {
                $item['net'] = round($item['net'], 2);
            }

            return $item;
        }, $rows);
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
            $aggregatedDataKeyedById = StatsAggregation::query()
                ->where('filter_hash', $filters->makeHash())
                ->whereBetween('time', [
                    $filterConditions['startDateStart'],
                    $filterConditions['endDateEnd'],
                ])
                ->orderBy('time', 'desc')
                ->pluck('data', 'id');

            // Refresh selected_at so the GC (ClearOldStatsAggregations) keeps hot
            // rows. Throttled to avoid a write on every single read: the GC window
            // is 400 days, so an hours-stale selected_at is irrelevant.
            StatsAggregation::whereIn('id', $aggregatedDataKeyedById->keys())
                ->where('selected_at', '<', now()->subHours(6))
                ->update(['selected_at' => now()]);

            $aggregatedData = $this->formatAggregatedData($aggregatedDataKeyedById);
        }

        if ($aggregatedData->isEmpty()) {
            $aggregatedData = $this->getRawData($filterConditions, $filters->projectId, $filters);
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

        $teamCondition = ' stats.team_id = '.AppHelper::getTeamId().' ';

        $projectCondition = '';
        if (! empty($filters->projectId)) {
            $projectCondition .= " AND stats.project_id = {$filters->projectId} ";
        }

        $conditions['teamCondition'] = $teamCondition;
        $conditions['projectCondition'] = $teamCondition.$projectCondition;

        $trackingCondition = '';

        if (! empty($filters->locationCountry)) {
            $locationCountryCondition = ($filters->locationCountry === StatsFilterDto::TRACK_ORGANIC) ? ' IS NULL ' : " = {$filters->locationCountry} ";
            $trackingCondition .= " AND stats.stats_location_country_id $locationCountryCondition ";
        }

        if (! empty($filters->locationRegion)) {
            $locationRegionCondition = ($filters->locationRegion === StatsFilterDto::TRACK_ORGANIC) ? ' IS NULL ' : " = {$filters->locationRegion} ";
            $trackingCondition .= " AND stats.stats_location_region_id $locationRegionCondition ";
        }

        if (! empty($filters->locationCity)) {
            $locationCityCondition = ($filters->locationCity === StatsFilterDto::TRACK_ORGANIC) ? ' IS NULL ' : " = {$filters->locationCity} ";
            $trackingCondition .= " AND stats.stats_location_city_id $locationCityCondition ";
        }

        if (! empty($filters->deviceType)) {
            $deviceTypeCondition = ($filters->deviceType === StatsFilterDto::TRACK_ORGANIC) ? ' IS NULL ' : " = {$filters->deviceType} ";
            $trackingCondition .= " AND stats.stats_device_type_id $deviceTypeCondition ";
        }

        if (! empty($filters->devicePlatform)) {
            $devicePlatformCondition = ($filters->devicePlatform === StatsFilterDto::TRACK_ORGANIC) ? ' IS NULL ' : " = {$filters->devicePlatform} ";
            $trackingCondition .= " AND stats.stats_device_platform_id $devicePlatformCondition ";
        }

        if (! empty($filters->deviceBrowser)) {
            $deviceBrowserCondition = ($filters->deviceBrowser === StatsFilterDto::TRACK_ORGANIC) ? ' IS NULL ' : " = {$filters->deviceBrowser} ";
            $trackingCondition .= " AND stats.stats_device_browser_id $deviceBrowserCondition ";
        }

        if (! empty($filters->trackReferer)) {
            $trackRefererCondition = ($filters->trackReferer === StatsFilterDto::TRACK_ORGANIC) ? ' IS NULL ' : " = {$filters->trackReferer} ";
            $trackingCondition .= " AND stats.stats_track_referer_id $trackRefererCondition ";
        }

        if (! empty($filters->trackSource)) {
            $trackSourceCondition = ($filters->trackSource === StatsFilterDto::TRACK_ORGANIC) ? ' IS NULL ' : " = {$filters->trackSource} ";
            $trackingCondition .= " AND stats.stats_track_source_id $trackSourceCondition ";
        }

        if (! empty($filters->trackMedium) && $filters->trackSource !== StatsFilterDto::TRACK_ORGANIC) {
            $trackingCondition .= " AND stats.stats_track_medium_id = {$filters->trackMedium} ";
        }
        if (! empty($filters->trackCampaign) && $filters->trackSource !== StatsFilterDto::TRACK_ORGANIC) {
            $trackingCondition .= " AND stats.stats_track_campaign_id = {$filters->trackCampaign} ";
        }
        if (! empty($filters->trackTerm) && $filters->trackSource !== StatsFilterDto::TRACK_ORGANIC) {
            $trackingCondition .= " AND stats.stats_track_term_id = {$filters->trackTerm} ";
        }
        if (! empty($filters->trackContent) && $filters->trackSource !== StatsFilterDto::TRACK_ORGANIC) {
            $trackingCondition .= " AND stats.stats_track_content_id = {$filters->trackContent} ";
        }

        if (! empty($filters->pageEntry)) {
            $pageEntryCondition = ($filters->pageEntry === StatsFilterDto::TRACK_ORGANIC) ? ' IS NULL ' : " = {$filters->pageEntry} ";
            $trackingCondition .= " AND stats.stats_page_entry_id $pageEntryCondition ";
        }

        $conditions['trackingCondition'] = '';
        $conditions['trackingConditionForEvents'] = '';
        $conditions['trackingConditionForEventsCe'] = '';

        // Conditions on the user/visitor entity tables (aliased su/sv). They are
        // appended to the RIGHT JOINs of the standard grouped queries and used
        // standalone by the grouped properties query.
        $userEntityConditions = '';
        $visitorEntityConditions = '';

        if (! empty($trackingCondition)) {
            $conditions['trackingCondition'] = $trackingCondition;
            $userEntityConditions .= ' '.str_replace('stats.', 'su.', $trackingCondition);
            $visitorEntityConditions .= ' '.str_replace('stats.', 'sv.', $trackingCondition);

            $visitorCondition = str_replace('stats.', 'sv.', $trackingCondition);
            $conditions['trackingConditionForEvents'] = " AND stats.stats_visitor_id IN (SELECT sv.id FROM stats_visitors sv WHERE sv.project_id = stats.project_id{$visitorCondition}) ";
            $conditions['trackingConditionForEventsCe'] = " AND ce.stats_visitor_id IN (SELECT sv.id FROM stats_visitors sv WHERE sv.project_id = ce.project_id{$visitorCondition}) ";
        }

        $conditions['eventConditionEventName'] = '';
        $conditions['eventConditionEventNameCe'] = '';
        $conditions['eventConditionGroup'] = '';
        $conditions['eventConditionUser'] = '';
        $conditions['eventConditionVisitor'] = '';
        $conditions['propertyConditionUser'] = '';
        $conditions['propertyConditionVisitor'] = '';
        $conditions['propertyConditionForEvents'] = '';
        $conditions['propertyConditionForEventsCe'] = '';
        $conditions['propertyConditionForVisitors'] = '';

        // Custom property filter: narrow users/visitors to the entities bound to
        // the selected property value. Lookups are project-scoped, so the chosen
        // value id is expanded to every equivalent (same property name + value
        // string) id of the team, which makes the filter work on the "All
        // projects" view too.
        if (! empty($filters->propertyValue)) {
            $referenceValue = StatsCustomPropertyValue::with('name')->find($filters->propertyValue);

            if ($referenceValue !== null) {
                $propertyValueIds = StatsCustomPropertyService::equivalentValueIds($referenceValue)->implode(',');

                $userPropertyCondition = fn (string $id, string $table) => "{$id} IN (SELECT sp.propertyable_id FROM stats_custom_properties sp WHERE sp.propertyable_type = 'stats_user' AND sp.stats_custom_property_value_id IN ({$propertyValueIds}) AND sp.project_id = {$table}.project_id)";
                $visitorPropertyCondition = fn (string $id, string $table) => "CAST({$id} AS varchar) IN (SELECT sp.propertyable_id FROM stats_custom_properties sp WHERE sp.propertyable_type = 'stats_visitor' AND sp.stats_custom_property_value_id IN ({$propertyValueIds}) AND sp.project_id = {$table}.project_id)";

                $conditions['propertyConditionUser'] = ' AND '.$userPropertyCondition('stats.id', 'stats').' ';
                $conditions['propertyConditionVisitor'] = ' AND '.$visitorPropertyCondition('stats.id', 'stats').' ';

                $userEntityConditions .= ' AND '.$userPropertyCondition('su.id', 'su').' ';
                $visitorEntityConditions .= ' AND '.$visitorPropertyCondition('sv.id', 'sv').' ';

                // events grouped query: only events triggered by an entity bound to the value
                $conditions['propertyConditionForEvents'] = ' AND ('.$userPropertyCondition('stats.stats_user_id', 'stats').' OR '.$visitorPropertyCondition('stats.stats_visitor_id', 'stats').') ';
                $conditions['propertyConditionForEventsCe'] = str_replace('stats.', 'ce.', $conditions['propertyConditionForEvents']);
                $conditions['propertyConditionForVisitors'] = ' AND '.$visitorPropertyCondition('stats.id', 'stats').' ';
            }
        }

        if (! empty($filters->customEventName)) {
            $eventId = $filters->customEventName;

            $conditions['eventConditionEventName'] = " AND stats.stats_custom_event_name_id = {$eventId} ";
            $conditions['eventConditionEventNameCe'] = " AND ce.stats_custom_event_name_id = {$eventId} ";
            // events grouped query: narrow the buckets to the selected event itself
            $conditions['eventConditionGroup'] = " AND stats.id = {$eventId} ";

            $userEventCondition = fn (string $id, string $table) => "{$id} IN (SELECT DISTINCT ce_f.stats_user_id FROM stats_custom_events ce_f WHERE ce_f.stats_custom_event_name_id = {$eventId} AND ce_f.project_id = {$table}.project_id AND ce_f.stats_user_id IS NOT NULL)";
            $visitorEventCondition = fn (string $id, string $table) => "{$id} IN (SELECT DISTINCT ce_f.stats_visitor_id FROM stats_custom_events ce_f WHERE ce_f.stats_custom_event_name_id = {$eventId} AND ce_f.project_id = {$table}.project_id AND ce_f.stats_visitor_id IS NOT NULL)";

            $conditions['eventConditionUser'] = ' AND '.$userEventCondition('stats.id', 'stats').' ';
            $conditions['eventConditionVisitor'] = ' AND '.$visitorEventCondition('stats.id', 'stats').' ';

            $userEntityConditions .= ' AND '.$userEventCondition('su.id', 'su').' ';
            $visitorEntityConditions .= ' AND '.$visitorEventCondition('sv.id', 'sv').' ';
        }

        $conditions['userEntityConditions'] = $userEntityConditions;
        $conditions['visitorEntityConditions'] = $visitorEntityConditions;
        $conditions['trackingUserJoinConditions'] = 'RIGHT JOIN stats_users su ON su.id = stats.stats_user_id AND su.project_id = stats.project_id'.$userEntityConditions;
        $conditions['trackingVisitorJoinConditions'] = 'RIGHT JOIN stats_visitors sv ON sv.id = stats.stats_visitor_id AND sv.project_id = stats.project_id'.$visitorEntityConditions;

        return $conditions;
    }

    /**
     * Detect missing aggregation entries (including internal gaps) and fetch raw data for them.
     * Previously, only boundary dates (before first / after last cached key) were detected,
     * which missed gaps in the middle of the cached range caused by partial invalidation.
     */
    private function getMissingAggregatedData(Collection $aggregatedData, StatsFilterDto $filters): Collection
    {
        $filterConditions = $this->getFilterConditions($filters);
        $dateFormatClear = $filters->rangeType->dateFormatClear()['php'];

        $period = CarbonPeriodImmutable::create(
            $filterConditions['startDateStart'],
            '1 '.$filterConditions['intervalType']->value,
            $filterConditions['endDateStart']
        )->toArray();

        $missingDates = [];
        foreach ($period as $date) {
            $dateKey = trim(CarbonImmutable::parse($date)->format($dateFormatClear));
            if (! $aggregatedData->has($dateKey)) {
                $missingDates[] = CarbonImmutable::parse($date);
            }
        }

        if (empty($missingDates)) {
            return collect();
        }

        $missingAggregatedData = collect();
        $ranges = $this->groupContiguousDates($missingDates, $filterConditions['intervalType']);

        foreach ($ranges as $range) {
            $rangeFilters = new StatsFilterDto(...array_merge((array) $filters, [
                'rangeAmount' => count($range),
                'rangeStart' => end($range),
            ]));

            $missingAggregatedData = $missingAggregatedData->merge(
                $this->getRawData($this->getFilterConditions($rangeFilters), $rangeFilters->projectId, $rangeFilters)
            );
        }

        return $missingAggregatedData;
    }

    /**
     * @param  CarbonImmutable[]  $dates
     * @return CarbonImmutable[][]
     */
    private function groupContiguousDates(array $dates, \Stringable $intervalType): array
    {
        usort($dates, fn (CarbonImmutable $a, CarbonImmutable $b) => $a <=> $b);

        $ranges = [];
        $currentRange = [$dates[0]];

        for ($i = 1; $i < count($dates); $i++) {
            $expected = $currentRange[count($currentRange) - 1]->add(1, $intervalType->toString());

            if ($dates[$i]->equalTo($expected)) {
                $currentRange[] = $dates[$i];
            } else {
                $ranges[] = $currentRange;
                $currentRange = [$dates[$i]];
            }
        }
        $ranges[] = $currentRange;

        return $ranges;
    }

    public function getRawData(array $filterConditions, ?int $projectId, ?StatsFilterDto $filters = null): Collection
    {
        // Lazy carry-forward: instead of rescanning the whole history for the
        // lt_* baseline, reuse the accumulated lt_* of the cached bucket directly
        // before the range start (if present). Only the lt_* CTEs are affected;
        // the per-bucket pd_* values are still computed raw.
        $baselineSeed = $filters !== null ? $this->getCarryForwardBaseline($filters, $filterConditions) : null;

        $builder = new StatsAggregationsSqlBuilder(
            $this->getTrackablePersonByProject(Project::find($projectId)),
            seedLtBaseline: $baselineSeed !== null,
        );

        $sql = strtr($builder->build(), $this->formatFilter($filterConditions));
        // echo nl2br($sql);die;

        $dataArray = $this->getDataArray($sql);
        $this->applyContinuousLTAggregation($dataArray, $baselineSeed ?? []);

        return $this->formatAggregatedData(collect($dataArray));
    }

    /**
     * Resolve the lt_* baseline for a cold range from the cached predecessor
     * bucket instead of rescanning history. The bucket directly before the range
     * start already stores its accumulated lt_* (lifetime total up to and
     * including that bucket), which equals the sum of everything before the range
     * start, exactly what the lt_* CTEs would otherwise scan for. Returns null
     * (fall back to the full scan) when no such cached predecessor exists.
     *
     * @return array<string, int|float>|null
     */
    private function getCarryForwardBaseline(StatsFilterDto $filters, array $filterConditions): ?array
    {
        if (! config('app.stats_aggregation_enabled')) {
            return null;
        }

        $dateFormatClear = $filters->rangeType->dateFormatClear()['php'];

        $predecessorKey = trim(
            CarbonImmutable::parse($filterConditions['startDateStart'])
                ->sub(1, $filterConditions['intervalType']->toString())
                ->format($dateFormatClear)
        );

        $predecessor = StatsAggregation::query()
            ->where('filter_hash', $filters->makeHash())
            ->where('type', $filters->rangeType->value)
            ->where('time', $predecessorKey)
            ->first(['id', 'data']);

        if ($predecessor === null) {
            return null;
        }

        $seed = [];
        foreach ($predecessor->data as $key => $value) {
            if (Str::startsWith($key, 'lt_')) {
                $seed[$key] = $value;
            }
        }

        return $seed ?: null;
    }

    /**
     * Accumulated lt_* carried forward from the cached predecessor bucket.
     *
     * @param  array<string, int|float>  $baselineSeed
     */
    private function applyContinuousLTAggregation(array &$dataArray, array $baselineSeed = []): void
    {
        $dataArray = array_reverse($dataArray);

        // Seed the oldest bucket's lt_* with the carried-forward baseline. The
        // forward propagation below then rolls it through the whole range. Only
        // the oldest row matters, every later row's lt_* gets overwritten by the
        // propagation step before it is read.
        if (! empty($baselineSeed) && ! empty($dataArray)) {
            foreach ($baselineSeed as $ltKey => $seedValue) {
                if (array_key_exists($ltKey, $dataArray[0])) {
                    $dataArray[0][$ltKey] += $seedValue;
                }
            }
        }

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
     * The same counts for data-imports.
     *
     * @throws Exception
     */
    public function invalidateByStatsUpdate(BaseStat $stat): void
    {
        foreach (StatsRangeType::cases() as $case) {
            $this->invalidateCaseByStatsUpdate($stat, $case);
        }
    }

    /*
     * it's important to convert the time to the corresponding range types (days, weeks, months)
     * for instance: if the update time is 2024-05-15, you'll also want to delete form its corresponding week,
     * which starts at 2024-05-13 (same counts for months)
     */
    public function invalidateCaseByStatsUpdate(BaseStat $stat, StatsRangeType $case): void
    {
        $start = $case->rangeStart($stat->time)->format($case->dateFormatClear()['php']);

        StatsAggregation::where([
            ['time', '>=', $start],
            ['type', '=', $case->value],
        ])->where(function ($query) use ($stat) {
            // stats must be re-aggregated on a team basis
            $query->where(function ($query) use ($stat) {
                $query->where('team_id', '=', $stat->team_id)->where('project_id', '=', null);
            })
            // stats must be re-aggregated on project basis
                ->orWhere(function ($query) use ($stat) {
                    $query->where('team_id', '=', $stat->team_id)->where('project_id', '=', $stat->project_id);
                });
        })->delete();
    }

    public function clearByProject(Project $project): void
    {
        StatsAggregation::where(function ($query) use ($project) {
            // stats must be re-aggregated on a team basis
            $query->where(function ($query) use ($project) {
                $query->where('team_id', '=', $project->team_id)->where('project_id', '=', null);
            })
            // stats must be re-aggregated on a project basis
                ->orWhere(function ($query) use ($project) {
                    $query->where('team_id', '=', $project->team_id)->where('project_id', '=', $project->id);
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
