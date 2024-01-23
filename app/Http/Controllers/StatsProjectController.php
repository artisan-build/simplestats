<?php

namespace App\Http\Controllers;

use App\DTOs\StatsRangeFilterDto;
use App\Enums\StatsRange;
use App\Http\Requests\StatsProjectRequest;
use App\Models\Project;
use App\Models\StatsTrackCampaign;
use App\Models\StatsTrackContent;
use App\Models\StatsTrackMedium;
use App\Models\StatsTrackSource;
use App\Models\StatsTrackTerm;
use App\Services\StatsProjectService;
use Illuminate\Support\Facades\Date;
use Inertia\Inertia;

class StatsProjectController extends Controller
{
    public function __invoke(StatsProjectRequest $request, Project $project, StatsProjectService $statsProjectService)
    {
        $filterOrganic = ['id' => -1, 'name' => 'Organic'];
        $filterAll = ['id' => 0, 'name' => 'All'];

        $project->load('instances');
        $project->instances->prepend($filterAll);

        $filters = StatsRangeFilterDto::fromRequest($request);

        $project->stats = $statsProjectService->getStats($filters);

        $filtersFormatted = array_merge((array) $filters, [
            'rangeStart' => $filters->rangeStart->format('Y-m-d')
        ]);
        unset($filtersFormatted['projectId']);

        // closure (do not evaluate yet)
        $trackingMediaCallback = fn()       => StatsTrackMedium::where('project_id', $project->id)->get()->prepend($filterAll);
        $trackingCampaignsCallback = fn()   => StatsTrackCampaign::where('project_id', $project->id)->get()->prepend($filterAll);
        $trackingTermsCallback = fn()       => StatsTrackTerm::where('project_id', $project->id)->get()->prepend($filterAll);
        $trackingContentsCallback = fn()    => StatsTrackContent::where('project_id', $project->id)->get()->prepend($filterAll);

        $data = [
            'project' => $project,
            'filters' => $filtersFormatted,
            'rangeTypes' => $statsProjectService->getRangeTypes(),
            'trackingSources' => StatsTrackSource::where('project_id', $project->id)->get()->prepend($filterOrganic)->prepend($filterAll),
            'trackingMedia'     => Inertia::lazy(fn() => $trackingMediaCallback()),
            'trackingCampaigns' => Inertia::lazy(fn() => $trackingCampaignsCallback()),
            'trackingTerms'     => Inertia::lazy(fn() => $trackingTermsCallback()),
            'trackingContents'  => Inertia::lazy(fn() => $trackingContentsCallback()),
        ];

        // do not lazy load if the specific tracking isset...
        if (!empty($filters->trackSource)) $data['trackingMedia']       = $trackingMediaCallback();
        if (!empty($filters->trackMedium)) $data['trackingCampaigns']   = $trackingCampaignsCallback();
        if (!empty($filters->trackCampaign)) $data['trackingTerms']     = $trackingTermsCallback();
        if (!empty($filters->trackTerm)) $data['trackingContents']      = $trackingContentsCallback();

        return Inertia::render('Stats/Show', $data);
    }
}
