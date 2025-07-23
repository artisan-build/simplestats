<?php

namespace App\Http\Controllers;

use App\DTOs\StatsFilterDto;
use App\Enums\StatsRangeType;
use App\Http\Requests\StatsFilterRequest;
use App\Services\RefererService;
use App\Services\StatsAggregationsService;
use App\Services\StatsDetailsService;
use App\Services\StatsService;
use App\Services\SubscriptionService;
use Exception;
use Illuminate\Http\Request;
use Inertia\Inertia;

class StatsController extends Controller
{
    /**
     * @throws Exception
     */
    public function index(
        StatsFilterRequest $request,
        StatsDetailsService $statsDetailsService,
        SubscriptionService $subscriptionService,
        RefererService $refererService,
    ) {
        $user = $request->user();
        // hack: as project_id and instance_id may be defined in the request, we need to check $_GET
        $hasFilterRequest = ! empty($_GET);

        $teamStats = $user->currentTeam()->with('projects', function ($query) use ($request) {
            $query->with('instances', function ($query) use ($request) {
                $query->where('project_id', $request->projectId);
            });
        })->get()->first();

        // do this before prepend all filters...
        $teamStats->totalProjectsCount = $teamStats->projects->count(); // here we use the alreay loaded collection
        $teamStats->totalInstancesCount = $teamStats->instances()->count(); // here we use the relationship

        // we need to do prepend before keyBy('id')
        $teamStats->projects->prepend($statsDetailsService->getAllFilterFor('Projects'));

        // we can't just do: $teamStats->projects = $teamStats->projects->keyBy('id');
        // this would not update $teamStats cause of laravel intern relation management...
        $projects = $teamStats->projects->keyBy('id');
        if ($request->projectId && $projects[$request->projectId]['id'] !== StatsFilterDto::TRACK_ALL) {
            $selectedProject = $projects[$request->projectId];
            // we need to do prepend before keyBy('id')
            $selectedProject->instances->prepend($statsDetailsService->getAllFilterFor('Instances'));
            $selectedProject->setRelation('instances', $selectedProject->instances->keyBy('id'));
        }

        $teamStats->setRelation('projects', $projects);

        $userFilterSettings = $user->settings['statsFilters'][$user->current_team_id] ?? [];
        if (! $hasFilterRequest && ! empty($userFilterSettings)) {
            $request = new StatsFilterRequest($userFilterSettings);
        } else {
            $user->updateSettings('statsFilters.'.$user->current_team_id, $request->all());
        }

        $filters = StatsFilterDto::fromRequest($request);
        $teamStats->stats = $statsDetailsService->getStats($filters);

        if ($filters->comparison && $filters->rangeType !== StatsRangeType::YEAR) {
            $teamStats->statsPrev = array_values($statsDetailsService->getStats(
                StatsService::createPrevFiltersForComparison($filters),
            ));
        }

        $data = [
            'teams' => $user->allTeams(),
            'teamStats' => $teamStats,
            'filters' => StatsService::formatFilters($filters),
            'statsGroups' => StatsService::getStatsTypesCamel(['model']),
            'statsGroupNames' => StatsService::getFilterStatsTypeNames($filters),
            'statsStartingAt' => app(StatsService::class)->getStatsStartingAt([
                'team_id' => $user->team->id,
                'project_id' => $filters->projectId,
                'id' => $filters->instanceId,
            ]),
            'topReferers' => $refererService->getTopReferers(),
            'rangeTypes' => $statsDetailsService->getRangeTypes(),
            'rangeCurrent' => now()->format($filters->rangeType->dateFormat()['php']),
            'freePlanWarning' => ['show' => StatsService::showFreePlanWarning($user)],
            'readDocsWarning' => ['show' => StatsService::showReadDocsWarning($user)],
            'subscriptionPlanAlarm' => $subscriptionService->getPlanAlarm($user->team),
            'trackingUsageBits' => StatsService::getTrackingUsages()->mapWithKeys(function ($item, $key) {
                return [$key => $item['bit']];
            }),
            'comparisonTypes' => StatsService::getComparisonTypes(),
        ];

        return Inertia::render('Dashboard/Index', $data);
    }

    public function groups(StatsFilterRequest $request, StatsAggregationsService $statsAggregationsService)
    {
        if (isset($request->groupType)) {
            $request->user()->updateSettings("selectedGroupTypes.$request->projectId.$request->groupType", $request->statsType);
        }

        // ->values()->toArray() makes sure the indexes are int-numeric and not string-numeric
        // (which would result in a js object). This happens cause of the reject at the end of "getAggregatedGroupedData"
        return $statsAggregationsService->getAggregatedGroupedData(
            StatsFilterDto::fromRequest($request),
            $request->statsType,
            $request->statsSort,
            $request->limit ?? 5, //must be equal to Load more in StatsOverviewGroup.vue
        )->values()->toArray();
    }

    public function setSeries(Request $request)
    {
        $chartIndex = $request->chartIndex;
        $seriesIndex = $request->seriesIndex;

        $request->user()->updateSettings("selectedSeries.$chartIndex", $seriesIndex);

        return response()->json();
    }

    public function hideFreePlanWarning(Request $request)
    {
        $request->user()->updateSettings('hideFreePlanWarning', true);

        return back();
    }

    public function hideReadDocsWarning(Request $request)
    {
        $request->user()->updateSettings('hideReadDocsWarning', true);

        return back();
    }
}
