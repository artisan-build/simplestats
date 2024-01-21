<?php

namespace App\Http\Controllers;

use App\Http\Requests\StatsProjectRequest;
use App\Models\Project;
use App\Services\StatsOverviewService;
use App\Services\StatsProjectService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class StatsProjectController extends Controller
{
    public function __invoke(StatsProjectRequest $request, Project $project, StatsProjectService $statsProjectService)
    {
        $project->load('instances');

        $project->stats = $statsProjectService->getStats([
            'project_id' => $project->id,
            'instance_id' => null,
            'range_type' => 'days',
            'range_amount' => 7,
            'range_start' => now(),
        ]);
//dd($project);
        return Inertia::render('Stats/Show', compact('project'));
    }
}
