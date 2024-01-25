<?php

namespace App\Http\Controllers;

use App\DTOs\StatsRangeFilterDto;
use App\Http\Requests\StatsDetailsRequest;
use App\Models\StatsTrackCampaign;
use App\Models\StatsTrackContent;
use App\Models\StatsTrackMedium;
use App\Models\StatsTrackSource;
use App\Models\StatsTrackTerm;
use App\Services\StatsDetailsService;
use Inertia\Inertia;

class StatsDetailsController extends Controller
{
    public function __invoke(StatsDetailsRequest $request, StatsDetailsService $statsDetailsService)
    {
        $filterAll = ['id' => StatsRangeFilterDto::TRACK_ALL, 'name' => 'All'];
        $filterOrganic = ['id' => StatsRangeFilterDto::TRACK_ORGANIC, 'name' => 'Organic'];

        $teamStats = auth()->user()->currentTeam()->with('projects', function ($query) use ($request) {
            $query->with('instances', function ($query) use ($request) {
                $query->where('project_id', $request->projectId);
            });
        })->get()->first();

        // we need to do prepend before keyBy('id')
        $teamStats->projects->prepend($filterAll);

        // we can't just do: $teamStats->projects = $teamStats->projects->keyBy('id');
        // this would not update $teamStats cause of laravel intern relation management...
        $projects = $teamStats->projects->keyBy('id');
        if ($selectedProject = $projects[$request->projectId] ?? false) {
            // we need to do prepend before keyBy('id')
            $selectedProject->instances->prepend($filterAll);
            $selectedProject->setRelation('instances', $selectedProject->instances->keyBy('id'));
        }

        $teamStats->setRelation('projects', $projects);

        $filters = StatsRangeFilterDto::fromRequest($request);

        $teamStats->stats = $statsDetailsService->getStats($filters);

        $filtersFormatted = array_merge((array) $filters, [
            'rangeStart' => $filters->rangeStart->format('Y-m-d')
        ]);

        // closure (do not evaluate yet)
        $trackingMediaCallback = fn()       => StatsTrackMedium::where('project_id', $filters->projectId)->get()->prepend($filterAll);
        $trackingCampaignsCallback = fn()   => StatsTrackCampaign::where('project_id', $filters->projectId)->get()->prepend($filterAll);
        $trackingTermsCallback = fn()       => StatsTrackTerm::where('project_id', $filters->projectId)->get()->prepend($filterAll);
        $trackingContentsCallback = fn()    => StatsTrackContent::where('project_id', $filters->projectId)->get()->prepend($filterAll);

        $data = [
            'teamStats' => $teamStats,
            'filters' => $filtersFormatted,
            'rangeTypes' => $statsDetailsService->getRangeTypes(),
            'trackAllKey' => StatsRangeFilterDto::TRACK_ALL,
            'trackingSources' => StatsTrackSource::where('project_id', $filters->projectId)->get()->prepend($filterOrganic)->prepend($filterAll),
            'trackingMedia'     => Inertia::lazy(fn() => $trackingMediaCallback()),
            'trackingCampaigns' => Inertia::lazy(fn() => $trackingCampaignsCallback()),
            'trackingTerms'     => Inertia::lazy(fn() => $trackingTermsCallback()),
            'trackingContents'  => Inertia::lazy(fn() => $trackingContentsCallback()),
        ];

        // do not lazy load if the specific tracking isset...
        // this is hierarchical, each depends on the other (also used to hide select boxes if higher tracking was set to all)
        if ($filters->trackSource > StatsRangeFilterDto::TRACK_ALL) {
            $data['trackingMedia'] = $trackingMediaCallback();
            if ($filters->trackMedium > StatsRangeFilterDto::TRACK_ALL) {
                $data['trackingCampaigns'] = $trackingCampaignsCallback();
                if ($filters->trackCampaign > StatsRangeFilterDto::TRACK_ALL) {
                    $data['trackingTerms'] = $trackingTermsCallback();
                    if ($filters->trackTerm > StatsRangeFilterDto::TRACK_ALL) {
                        $data['trackingContents'] = $trackingContentsCallback();
                    }
                }
            }
        }

        return Inertia::render('Stats/Show', $data);
    }
}
