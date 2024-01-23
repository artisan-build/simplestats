<?php

namespace App\Http\Controllers;

use App\Enums\StatsRange;
use App\Http\Requests\StatsProjectRequest;
use App\Models\Project;
use App\Models\StatsTrackSource;
use App\Services\StatsProjectService;
use Illuminate\Support\Facades\Date;
use Inertia\Inertia;

class StatsProjectController extends Controller
{
    public function __invoke(StatsProjectRequest $request, Project $project, StatsProjectService $statsProjectService)
    {
        $project->load('instances');
        $project->instances->prepend(['id' => 0, 'name' => 'All']);

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
            ]
        ];

        $project->stats = $statsProjectService->getStats($filters);

        // after filters are passed to "getStats" chagne the format for frontend...
        unset($filters['project_id']); // this already comes through the route
        $filters['range']['start'] = $filters['range']['start']->format('Y-m-d');

        return Inertia::render('Stats/Show', [
            'project' => $project,
            'filters' => $filters,
            'rangeTypes' => $statsProjectService->getRangeTypes(),
            'tracking' => [
                'source' => StatsTrackSource::where('project_id', $project->id)->get()
                    ->prepend(['id' => -1, 'name' => 'Organic'])
                    ->prepend(['id' => 0, 'name' => 'All']),
            ],
        ]);
    }
}
