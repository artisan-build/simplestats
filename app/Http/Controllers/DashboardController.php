<?php

namespace App\Http\Controllers;

use App\DTOs\StatsFilterDto;
use App\Http\Requests\StatsFilterRequest;
use App\Models\StatsUser;
use App\Services\StatsDetailsService;
use App\Services\StatsService;
use App\Services\SubscriptionService;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function __invoke(StatsFilterRequest $request, StatsDetailsService $statsDetailsService, SubscriptionService $subscriptionService)
    {
        $user = $request->user();

        if ($request->has('rangeType') && $user->selected_range_type != $request->rangeType) {
            $user->update([
                'selected_range_type' => $request->rangeType,
            ]);
        }

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
        $data = [
            'teams' => $user->allTeams(),
            'teamStats' => $teamStats,
            'filters' => StatsService::formatFilters($filters),
            'statsGroups' => StatsService::getStatsTypesCamel(['model']),
            'statsGroupNames' => StatsService::getFilterStatsTypeNames($filters),
            'statsGroupsSelected' => session()->get('selectedGroupTypes') ?? [],
            'rangeTypes' => $statsDetailsService->getRangeTypes(),
            'rangeCurrent' => now()->format($filters->rangeType->dateFormat()['php']),
            'trackAllKey' => StatsFilterDto::TRACK_ALL,
            'showFreePlanWarning' => $teamOwner->sparkPlan()->name === SubscriptionService::PLAN_FREE,
            'subscriptionPlanAlarm' => $subscriptionService->checkPlanAlarm($user->team)
        ];

        return Inertia::render('Dashboard/Index', $data);
    }
}
