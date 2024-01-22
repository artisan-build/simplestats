<?php

namespace App\Http\Controllers;

use App\Enums\StatsRange;
use App\Http\Requests\StatsProjectRequest;
use App\Models\Project;
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
            'project_id' => $project->id,
            'instance_id' => $request->instance_id ?? 0,
            'range_type' => $request->range_type ?? 'days',
            'range_amount' => $request->range_amount ?? 7,
            'range_start' => ($request->range_start) ? Date::parse($request->range_start) : now(),
        ];

        $project->stats = $statsProjectService->getStats($filters);

        // after filters are passed to "getStats" chagne the format for frontend...
        $filters['range_start'] = $filters['range_start']->format('Y-m-d');

        return Inertia::render('Stats/Show', [
            'project' => $project,
            'filters' => $filters,
            'rangeTypes' => $statsProjectService->getRangeTypes(),
        ]);
    }
}
