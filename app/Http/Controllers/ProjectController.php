<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProjectStoreRequest;
use App\Http\Requests\ProjectUpdateRequest;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProjectController extends Controller
{
    public function index(Request $request): Response
    {
        $projects = Project::all();

        return Inertia::render('Projects/Index', compact('projects'));
    }

    public function create(Request $request): Response
    {
        return Inertia::render('Projects/Create');
    }

    public function store(ProjectStoreRequest $request): RedirectResponse
    {
        Project::create($request->validated());

        $request->session()->flash('flash.banner', 'Projects created!');

        return redirect()->route('projects.index');
    }

    public function show(Request $request, Project $project): Response
    {
        return Inertia::render('Projects/Show', compact('project'));
    }

    public function edit(Request $request, Project $project): Response
    {
        return Inertia::render('Projects/Edit', compact('project'));
    }

    public function update(ProjectUpdateRequest $request, Project $project): RedirectResponse
    {
        $project->update($request->validated());

        $request->session()->flash('flash.banner', 'Projects updated!');

        return redirect()->route('projects.index');
    }

    public function destroy(Request $request, Project $project): RedirectResponse
    {
        $project->delete();

        return redirect()->route('projects.index');
    }
}
