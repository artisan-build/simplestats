<?php

namespace App\Http\Controllers;

use App\Http\Requests\InstanceStoreRequest;
use App\Http\Requests\InstanceUpdateRequest;
use App\Models\Instance;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class InstanceController extends Controller
{
    public function index(Request $request): Response
    {
        $projects = Project::orderBy('created_at', 'desc')->get();
        $instances = Instance::orderBy('created_at', 'desc')->with('project')->paginate(10);
        $selectedProject = $request->selected_project_id ?? null;

        return Inertia::render('Instances/Index', compact('instances', 'projects', 'selectedProject'));
    }

    public function store(InstanceStoreRequest $request): RedirectResponse
    {
        $instance = Instance::create($request->validated());

        return redirect()->route('instances.show', $instance->id)->banner('Instance created: '.$instance->name);
    }

    public function show(Request $request, Instance $instance): Response
    {
        $instance->load('project');
        $projects = Project::orderBy('created_at', 'desc')->get();

        $permissions = [
            'canAddTeamMembers' => Gate::check('addInstanceMember', $instance),
            'canDeleteInstance' => Gate::check('delete', $instance),
            'canRemoveInstanceMembers' => Gate::check('removeInstanceMember', $instance),
            'canUpdateInstance' => Gate::check('update', $instance),
            'canUpdateInstanceMembers' => Gate::check('updateInstanceMember', $instance),
        ];

        return Inertia::render('Instances/Show', compact('instance', 'projects', 'permissions'));
    }

    public function update(InstanceUpdateRequest $request, Instance $instance): RedirectResponse
    {
        $instance->update($request->validated());

        return redirect()->back();
    }

    public function destroy(Request $request, Instance $instance): RedirectResponse
    {
        $instance->delete();

        return redirect()->route('instances.index')->banner('Instance deleted: '.$instance->name);
    }
}
