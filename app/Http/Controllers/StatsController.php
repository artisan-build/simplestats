<?php

namespace App\Http\Controllers;

use App\DTOs\StatsFilterDto;
use App\Http\Requests\StatsFilterRequest;
use App\Models\Instance;
use App\Models\Project;
use App\Models\StatsUser;
use App\Services\RefererService;
use App\Services\StatsAggregationsService;
use App\Services\StatsDetailsService;
use App\Services\StatsService;
use App\Services\SubscriptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Inertia\Inertia;

class StatsController extends Controller
{
    public function index(
        StatsFilterRequest $request,
        StatsDetailsService $statsDetailsService,
        SubscriptionService $subscriptionService,
        RefererService $refererService,
    ) {
        $user = $request->user();

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

        $filters = StatsFilterDto::fromRequest($request);
        $teamStats->stats = $statsDetailsService->getStats($filters);

        $teamOwner = $user->team->owner;
        $currentPlan = $teamOwner->sparkPlan();
        $hideFreePlanWarning = $user->settings['hideFreePlanWarning'] ?? false;
        $data = [
            'teams' => $user->allTeams(),
            'teamStats' => $teamStats,
            'filters' => StatsService::formatFilters($filters),
            'statsGroups' => StatsService::getStatsTypesCamel(['model']),
            'statsGroupNames' => StatsService::getFilterStatsTypeNames($filters),
            'statsStartingAt' => app(StatsService::class)->getStatsStartingAt(
                Instance::find($filters->instanceId) ?? Project::find($filters->projectId) ?? $user->currentTeam
            ),
            'topReferers' => $refererService->getTopReferers(),
            'rangeTypes' => $statsDetailsService->getRangeTypes(),
            'rangeCurrent' => now()->format($filters->rangeType->dateFormat()['php']),
            'freePlanWarning' => [
                'show' => $currentPlan->name === SubscriptionService::PLAN_FREE && ! $hideFreePlanWarning,
                'users' => $currentPlan->options[StatsUser::class]
            ],
            'subscriptionPlanAlarm' => $subscriptionService->checkPlanAlarm($user->team)
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
        $request->user()->updateSettings("hideFreePlanWarning", true);

        return back();
    }
}
