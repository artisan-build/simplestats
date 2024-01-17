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

class ProjectInstanceController extends Controller
{
    public function index(Request $request, Project $project): Response
    {
        $projects = Project::orderBy('created_at', 'desc')->get();
        $instances = Instance::orderBy('created_at', 'desc')->with('project')->paginate(10);

        return Inertia::render('Instances/Index', compact('instances', 'projects', 'project'));
    }

    public function store(InstanceStoreRequest $request, Project $project): RedirectResponse
    {
        $instance = $project->instances()->create($request->validated());

        return redirect()->route('projects.instances.show', [$project->id, $instance->id])->with('flash', [
            'token' => $instance->tokens->first()->token_plain_part,
            'banner' => 'Instance created!',
        ]);
    }

    public function show(Request $request, Project $project, Instance $instance): Response
    {
        // TODO check permission for token...
        $instance->load('project', 'tokens');
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

    public function update(InstanceUpdateRequest $request, Project $project, Instance $instance): RedirectResponse
    {
        $instance->update($request->validated());

        return redirect()->back();
    }

    public function destroy(Request $request, Project $project, Instance $instance): RedirectResponse
    {
        $instance->delete();

        return redirect()->route('projects.instances.index', $instance->project_id)->banner('Instance deleted: '.$instance->name);
    }

    public function tokenRegenerate(Project $project, Instance $instance)
    {
        $instance->tokens()->delete();

        $token = $instance->createTokenPlain(
            $instance->name.Instance::TOKEN_NAME_POSTFIX,
        );

        return back()->with('flash', [
            'token' => $token->accessToken->token_plain_part,
            'banner' => 'Token regenerated!',
        ]);
    }

    public function tokenView(Project $project, Instance $instance)
    {
        $token = $instance->tokens()->first();

        return back()->with('flash', [
            'token' => $token->token_plain_part,
        ]);
    }
}
