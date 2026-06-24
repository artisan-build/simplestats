<?php

namespace App\Http\DataProviders;

use App\DTOs\StatsFilterDto;
use App\Enums\StatsRangePreset;
use App\Enums\StatsRangeType;
use App\Http\Requests\StatsFilterRequest;
use App\Models\Team;
use App\Models\User;
use App\Services\RefererService;
use App\Services\StatsCustomEventService;
use App\Services\StatsCustomPropertyService;
use App\Services\StatsDetailsService;
use App\Services\StatsService;
use App\Services\SubscriptionService;

/**
 * Shared base for the stats pages that render the StatsFilter (dashboard,
 * recurring revenue, ...): resolves the effective filter, loads the team's
 * projects, and assembles the common filter-shell props. Subclasses add only
 * their page-specific payload (e.g. period stats, MRR movements).
 */
abstract class AbstractStatsDataProvider extends AbstractDataProvider
{
    public function __construct(
        protected StatsFilterRequest $request,
        protected StatsDetailsService $statsDetailsService,
        protected SubscriptionService $subscriptionService,
        protected RefererService $refererService,
    ) {}

    /**
     * @return array<string, mixed>
     */
    protected function commonProps(User $user, StatsFilterDto $filters, Team $teamStats): array
    {
        $hiddenPresets = $user->settings['hiddenPresets'] ?? [];

        return [
            'teams' => $user->allTeams(),
            'teamStats' => $teamStats,
            'presets' => $this->buildPresets($user, $hiddenPresets),
            'allPresets' => StatsRangePreset::toOptions(),
            'hiddenPresets' => $hiddenPresets,
            'customPresets' => $user->customStatsRangePresets,
            'filters' => StatsService::formatFilters($filters),
            'statsGroups' => StatsService::getStatsTypesFormatted(['model']),
            'propertyDimensions' => StatsCustomPropertyService::getPropertyDimensions($filters),
            'hasCustomEvents' => StatsCustomEventService::hasCustomEvents($filters),
            'statsGroupNames' => StatsService::getFilterStatsTypeNames($filters),
            'topReferers' => $this->refererService->getTopReferers(),
            'rangeTypes' => $this->statsDetailsService->getRangeTypes(),
            'rangeCurrent' => now()->format($filters->rangeType->dateFormat()['php']),
            'freePlanWarning' => ['show' => StatsService::showFreePlanWarning($user)],
            'readDocsWarning' => ['show' => StatsService::showReadDocsWarning($user)],
            'subscriptionPlanAlarm' => $this->subscriptionService->getPlanAlarm($user->team),
            'trackingUsageBits' => StatsService::getTrackingUsages()->mapWithKeys(function ($item, $key) {
                return [$key => $item['bit']];
            }),
            'comparisonTypes' => StatsService::getComparisonTypes(),
        ];
    }

    protected function resolveEffectiveRequest(User $user): StatsFilterRequest
    {
        // hack: as project_id may be set within the request class, we need to check $_GET
        $hasFilterRequest = ! empty($_GET);

        $userFilterSettings = $user->settings['statsFilters'][$user->current_team_id] ?? [];

        if (! $hasFilterRequest && ! empty($userFilterSettings)) {
            return new StatsFilterRequest($userFilterSettings);
        }

        if ($this->request->has('preset') && $this->request->preset !== StatsRangePreset::CUSTOM->value) {
            // on preset (built-in or custom), we do not save the range
            $user->updateSettings('statsFilters.'.$user->current_team_id, $this->request->except([
                'range_start',
                'range_amount',
                'range_type',
            ]));
        }

        return $this->request;
    }

    protected function loadTeamStats(User $user, StatsFilterDto $filters): Team
    {
        $teamStats = $user->currentTeam()->with('projects')->get()->first();

        // do this before prepend all filters...
        $teamStats->totalProjectsCount = $teamStats->projects->count();

        // we need to do prepend before keyBy('id')
        $mergedUsageBits = $teamStats->projects->reduce(fn ($carry, $project) => $carry | $project['usage'], 0);
        $teamStats->projects->prepend($this->statsDetailsService->getAllFilterFor('Projects', $mergedUsageBits));

        // we can't just do: $teamStats->projects = $teamStats->projects->keyBy('id');
        // this would not update $teamStats cause of laravel intern relation management...
        $teamStats->setRelation('projects', $teamStats->projects->keyBy('id'));

        if ($filters->projectId !== StatsFilterDto::TRACK_ALL && ! $teamStats->projects->has($filters->projectId)) {
            $filters->projectId = StatsFilterDto::TRACK_ALL;
        }

        return $teamStats;
    }

    /**
     * @param  array<int, mixed>  $hiddenPresets
     * @return array<int, mixed>
     */
    protected function buildPresets(User $user, array $hiddenPresets): array
    {
        return collect(StatsRangePreset::toOptions())
            ->reject(fn ($p) => in_array($p['id'], $hiddenPresets))
            ->merge($user->customStatsRangePresets->map->toOption())
            ->sortBy('sortWeight')
            ->values()
            ->toArray();
    }

    /**
     * @return array{mrr: float, arr: float, mrr_prev: float}|null
     */
    protected function buildMrrSnapshot(Team $teamStats, StatsFilterDto $filters): ?array
    {
        if (! $this->projectTracksRecurring($teamStats, $filters)) {
            return null;
        }

        return $this->statsDetailsService->getMrrSnapshot($filters);
    }

    /**
     * @return array<int, array{date: string, date_clear: string, mrr: float, subscribers: int}>|null
     */
    protected function buildMrrTimeSeries(Team $teamStats, StatsFilterDto $filters): ?array
    {
        if (! $this->projectTracksRecurring($teamStats, $filters)) {
            return null;
        }

        return $this->statsDetailsService->getMrrTimeSeries($filters);
    }

    /**
     * @return array<int, array{date: string, date_clear: string, mrr: float, subscribers: int}>|null
     */
    protected function buildMrrTimeSeriesComparison(Team $teamStats, StatsFilterDto $filters): ?array
    {
        if (! $filters->comparison || $filters->rangeType === StatsRangeType::YEAR) {
            return null;
        }

        return $this->buildMrrTimeSeries($teamStats, StatsService::createPrevFiltersForComparison($filters));
    }

    /**
     * @return array<int, array<string, mixed>>|null
     */
    protected function buildMrrMovements(Team $teamStats, StatsFilterDto $filters): ?array
    {
        if (! $this->projectTracksRecurring($teamStats, $filters)) {
            return null;
        }

        return $this->statsDetailsService->getMrrMovements($filters);
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function buildMrrRetention(Team $teamStats, StatsFilterDto $filters): ?array
    {
        if (! $this->projectTracksRecurring($teamStats, $filters)) {
            return null;
        }

        return $this->statsDetailsService->getMrrRetention($filters);
    }

    /**
     * Gated on the explicit RECURRING intent bit (not on whether a subscription
     * payment already arrived) so a fresh subscription project shows an empty MRR
     * line from day one instead of the chart appearing only after its first payment.
     */
    protected function projectTracksRecurring(Team $teamStats, StatsFilterDto $filters): bool
    {
        $project = $teamStats->projects[$filters->projectId] ?? null;

        if ($project === null) {
            return false;
        }

        return (bool) ($project['usage'] & StatsService::TRACKING_USAGE_BITS_RECURRING);
    }
}
