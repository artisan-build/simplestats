<?php

namespace App\Http\Controllers;

use App\Enums\StatsRange;
use App\Http\Requests\StatsProjectRequest;
use App\Models\Project;
use App\Models\StatsTrackMedium;
use App\Models\StatsTrackSource;
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

        $filters = [
            'team_id' => $project->team_id,
            'project_id' => $project->id,
            'instance_id' => $request->instance_id ?? 0,
            'range' => [
                'type' => $request->range['type'] ?? 'days',
                'amount' => $request->range['amount'] ?? 7,
                'start' => (isset($request->range['start'])) ? Date::parse($request->range['start']) : now(),
            ],
            'track' => [
                'source' => $request->track['source'] ?? 0,
                'medium' => $request->track['medium'] ?? 0,
            ]
        ];

        $project->stats = $statsProjectService->getStats($filters);

        // after filters are passed to "getStats" chagne the format for frontend...
        unset($filters['project_id']); // this already comes through the route
        $filters['range']['start'] = $filters['range']['start']->format('Y-m-d');

        $tracking = [];
        $tracking['source'] = StatsTrackSource::where('project_id', $project->id)->get()->prepend($filterOrganic)->prepend($filterAll);
        $tracking['medium'] = StatsTrackMedium::where('project_id', $project->id)->get()->prepend($filterAll);

        return Inertia::render('Stats/Show', [
            'project' => $project,
            'filters' => $filters,
            'rangeTypes' => $statsProjectService->getRangeTypes(),
            'tracking' => $tracking,
        ]);
    }
}
