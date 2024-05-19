<?php

namespace App\Http\Controllers;

use App\DTOs\StatsRangeFilterDto;
use App\Enums\StatsRangeType;
use App\Http\Requests\DashboardRequest;
use App\Models\StatsTrackCampaign;
use App\Models\StatsTrackContent;
use App\Models\StatsTrackMedium;
use App\Models\StatsTrackSource;
use App\Models\StatsTrackTerm;
use App\Services\StatsDetailsService;
use App\Services\SubscriptionService;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function __invoke(DashboardRequest $request, StatsDetailsService $statsDetailsService, SubscriptionService $subscriptionService)
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
        if ($selectedProject = $projects[$request->projectId] ?? false) {
            // we need to do prepend before keyBy('id')
            $selectedProject->instances->prepend($statsDetailsService->getAllFilterFor('Instances'));
            $selectedProject->setRelation('instances', $selectedProject->instances->keyBy('id'));
        }

        $teamStats->setRelation('projects', $projects);

        $filters = StatsRangeFilterDto::fromRequest($request);
        $teamStats->stats = $statsDetailsService->getStats($filters);

        $filtersFormatted = array_merge((array) $filters, [
            'rangeStart' => $filters->rangeStart->format('Y-m-d')
        ]);

        // closure (do not evaluate yet)
        $trackingSourceCallback = fn()      => StatsTrackSource::where('project_id', $filters->projectId)->get()->prepend($statsDetailsService->getOrganicFilter())->prepend($statsDetailsService->getAllFilterFor('Sources'));
        $trackingMediaCallback = fn()       => StatsTrackMedium::where('project_id', $filters->projectId)->get()->prepend($statsDetailsService->getAllFilterFor('Mediums'));
        $trackingCampaignsCallback = fn()   => StatsTrackCampaign::where('project_id', $filters->projectId)->get()->prepend($statsDetailsService->getAllFilterFor('Campaigns'));
        $trackingTermsCallback = fn()       => StatsTrackTerm::where('project_id', $filters->projectId)->get()->prepend($statsDetailsService->getAllFilterFor('Terms'));
        $trackingContentsCallback = fn()    => StatsTrackContent::where('project_id', $filters->projectId)->get()->prepend($statsDetailsService->getAllFilterFor('Content'));

        $teamOwner = $user->team->owner;
        $data = [
            'teamStats' => $teamStats,
            'filters' => $filtersFormatted,
            'rangeTypes' => $statsDetailsService->getRangeTypes(),
            'rangeCurrent' => now()->format(StatsRangeType::dateFormats()[$filters->rangeType->value]['php']),
            'trackAllKey' => StatsRangeFilterDto::TRACK_ALL,
            'trackingSources' => $trackingSourceCallback(),
            'trackingMedia'     => $trackingMediaCallback(),
            'trackingCampaigns' => $trackingCampaignsCallback(),
            'trackingTerms'     => Inertia::lazy(fn() => $trackingTermsCallback()),
            'trackingContents'  => Inertia::lazy(fn() => $trackingContentsCallback()),
            'showFreePlanWarning' => $teamOwner->sparkPlan()->name === SubscriptionService::PLAN_FREE,
            'subscriptionPlanAlarm' => $subscriptionService->checkPlanAlarm($user->team)
        ];

        // do not lazy load if the specific tracking isset...
        if ($filters->extendedTracking > StatsRangeFilterDto::TRACK_ALL)   $data['trackingTerms'] = $trackingTermsCallback();
        if ($filters->extendedTracking > StatsRangeFilterDto::TRACK_ALL)       $data['trackingContents'] = $trackingContentsCallback();

        return Inertia::render('Dashboard/Index', $data);
    }
}
