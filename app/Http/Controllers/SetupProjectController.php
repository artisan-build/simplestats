<?php

namespace App\Http\Controllers;

use App\Actions\CreateProject;
use App\Actions\UpdateProject;
use App\Http\Requests\ProjectRequest;
use App\Models\Project;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SetupProjectController extends Controller
{
    public function index(Request $request)
    {
        return Inertia::render('Setup/Project', [
            'project' => $request->user()->currentTeam->projects()->first() ?? new Project,
            'usages' => Project::getUsages(),
            'flags' => Project::getFlags(),
        ]);
    }

    public function store(ProjectRequest $request)
    {
        $project = $request->user()->currentTeam->projects()->first();

        if ($project) {
            resolve(UpdateProject::class)($request->user(), $project, $request->validated());
        } else {
            resolve(CreateProject::class)($request->user(), $request->validated());
        }

        return to_route('setup.installation.index');
    }
}
