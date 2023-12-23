<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProjectStoreRequest;
use App\Http\Requests\ProjectUpdateRequest;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ProjectController extends Controller
{
    public function index(Request $request): Response
    {
        $projects = Project::orderBy('created_at', 'desc')->paginate(10);

        return Inertia::render('Projects/Index', compact('projects'));
    }

    public function store(ProjectStoreRequest $request): RedirectResponse
    {
        Project::create($request->validated());

        return redirect()->back();
    }

    public function show(Request $request, Project $project): Response
    {
        $project->load('instances');

        $permissions = [
            'canAddTeamMembers' => Gate::check('addProjectMember', $project),
            'canDeleteProject' => Gate::check('delete', $project),
            'canRemoveProjectMembers' => Gate::check('removeProjectMember', $project),
            'canUpdateProject' => Gate::check('update', $project),
            'canUpdateProjectMembers' => Gate::check('updateProjectMember', $project),
        ];

        return Inertia::render('Projects/Show', compact('project', 'permissions'));
    }

    public function update(ProjectUpdateRequest $request, Project $project): RedirectResponse
    {
        $project->update($request->validated());

        return redirect()->back();
    }

    public function destroy(Request $request, Project $project): RedirectResponse
    {
        $project->delete();

        return redirect()->route('projects.index')->banner('Project deleted: '.$project->name);
    }
}
