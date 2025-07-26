<?php

namespace App\Http\Controllers;

use App\Actions\CreateInstance;
use App\Actions\UpdateProject;
use App\Http\Requests\ProjectRequest;
use App\Models\Project;
use App\Services\StatsService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SetupProjectController extends Controller
{
    public function index(Request $request)
    {
        return Inertia::render('Setup/Project', [
            'project' => $request->user()->currentTeam->projects()->first(),
            'trackingUsages' => StatsService::getTrackingUsages(),
        ]);
    }

    public function store(ProjectRequest $request)
    {
        $project = $request->user()->currentTeam->projects()->first();
        $project = app(UpdateProject::class)($request->user(), $project, $request->validated());

        // the first instance is created automatically and named after the project
        if ($project->instances()->count() <= 0) {
            app(CreateInstance::class)($request->user(), $project, [
                'name' => $project->name,
            ]);
        }

        return redirect()->route('setup.installation.index');
    }
}
