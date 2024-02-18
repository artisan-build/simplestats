<?php

namespace App\Http\Controllers;

use App\Actions\CheckPasswordConfirmation;
use App\Http\Requests\InstanceStoreRequest;
use App\Http\Requests\InstanceUpdateRequest;
use App\Models\Instance;
use App\Models\Project;
use Illuminate\Contracts\Auth\StatefulGuard;
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
        $instance->load('project');

        if (Gate::check('viewInstanceToken', $instance)) {
            $instance->load('tokens');
        }

        $projects = Project::orderBy('created_at', 'desc')->get();

        return Inertia::render('Instances/Show', [
            'instance' => $instance,
            'projects' => $projects,
            'permissions' => [
                'canDeleteInstance' => Gate::check('delete', $instance),
                'canUpdateInstance' => Gate::check('update', $instance),
                'canRegenerateInstanceToken' => Gate::check('regenerateInstanceToken', $instance),
            ]
        ]);
    }

    public function update(InstanceUpdateRequest $request, Project $project, Instance $instance): RedirectResponse
    {
        $instance->update($request->validated());

        return redirect()->back();
    }

    public function destroy(Request $request, Project $project, Instance $instance, StatefulGuard $guard): RedirectResponse
    {
        app(CheckPasswordConfirmation::class)($guard, $request->user(), $request->password);

        $instance->delete();

        return redirect()->route('projects.show', $instance->project_id)->banner('Instance deleted: '.$instance->name);
    }

    public function tokenRegenerate(Request $request, Project $project, Instance $instance, StatefulGuard $guard)
    {
        $this->authorize('regenerateInstanceToken', $instance);

        app(CheckPasswordConfirmation::class)($guard, $request->user(), $request->password);

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
        $this->authorize('viewInstanceToken', $instance);

        $token = $instance->tokens()->first();

        return back()->with('flash', [
            'token' => $token->token_plain_part,
        ]);
    }
}
