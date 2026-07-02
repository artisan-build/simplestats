<?php

namespace App\Http\Controllers;

use App\Actions\CheckPasswordConfirmation;
use App\Actions\CreateProject;
use App\Actions\UpdateProject;
use App\Enums\DashboardPreset;
use App\Http\Requests\ProjectRequest;
use App\Models\Project;
use App\Services\StatsAggregationsService;
use Closure;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ProjectController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Project::class, 'project');
    }

    public function index(Request $request): Response
    {
        return Inertia::render('Projects/Index', [
            'projects' => Project::orderBy('created_at', 'desc')->paginate(10),
            'usages' => Project::getUsages(),
            'flags' => Project::getFlags(),
            'permissions' => [
                'canCreateProject' => Gate::check('create', Project::class),
            ],
        ]);
    }

    public function store(ProjectRequest $request): RedirectResponse
    {
        $project = app(CreateProject::class)($request->user(), $request->validated());

        $token = $project->tokens()->first();

        return redirect()->route('projects.show', $project->id)
            ->banner('Project created: '.$project->name)
            ->with('flash', [
                'token' => $token->token_plain_part,
            ]);
    }

    public function show(Request $request, Project $project): Response
    {
        if (Gate::check('viewProjectToken', $project)) {
            $project->load('tokens');
        }

        return Inertia::render('Projects/Show', [
            'project' => $project,
            'usages' => Project::getUsages(),
            'flags' => Project::getFlags(),
            'permissions' => [
                'canDeleteProject' => Gate::check('delete', $project),
                'canUpdateProject' => Gate::check('update', $project),
                'canCreateProject' => Gate::check('create', $project),
                'canRegenerateProjectToken' => Gate::check('regenerateProjectToken', $project),
            ],
        ]);
    }

    public function update(ProjectRequest $request, Project $project): RedirectResponse
    {
        $validated = $request->validated();

        if ($validated['usage'] != $project->usage) {
            app(StatsAggregationsService::class)->clearByProject($project);
            $request->user()->updateSettings('dashboardPreset', DashboardPreset::ADVANCED->value);
        }

        app(UpdateProject::class)($request->user(), $project, $validated);

        return redirect()->back();
    }

    public function updateImpersonateUrl(Request $request, Project $project): RedirectResponse
    {
        $this->authorize('update', $project);

        $request->merge([
            'impersonate_base_url' => filled($request->impersonate_base_url)
                ? trim($request->impersonate_base_url)
                : null,
        ]);

        $validated = $request->validate([
            'impersonate_base_url' => ['nullable', 'string', 'max:255', function (string $attribute, mixed $value, Closure $fail) {
                // {id} is a placeholder for the tracked user id and is not a valid URL
                // character, so validate the URL with a stand-in in its place.
                if (! filter_var(str_replace('{id}', '1', $value), FILTER_VALIDATE_URL)) {
                    $fail('The impersonate base URL must be a valid URL.');
                }
            }],
        ]);

        $url = $validated['impersonate_base_url'] ?? null;

        // Only trim a trailing slash in append mode; a token url is stored verbatim so
        // the {id} position is preserved exactly.
        if ($url !== null && ! str_contains($url, '{id}')) {
            $url = rtrim($url, '/');
        }

        $project->update(['impersonate_base_url' => $url]);

        return redirect()->back();
    }

    public function destroy(Request $request, Project $project, StatefulGuard $guard): RedirectResponse
    {
        app(CheckPasswordConfirmation::class)($guard, $request->user(), $request->password);

        app(StatsAggregationsService::class)->clearByProject($project);

        $project->delete();

        return redirect()->route('projects.index')->banner('Project deleted: '.$project->name);
    }

    public function tokenRegenerate(Request $request, Project $project, StatefulGuard $guard)
    {
        $this->authorize('regenerateProjectToken', $project);

        app(CheckPasswordConfirmation::class)($guard, $request->user(), $request->password);

        $project->tokens()->delete();

        $token = $project->createTokenPlain(Project::TOKEN_NAME);

        return back()->with('flash', [
            'token' => $token->accessToken->token_plain_part,
            'banner' => 'Token regenerated!',
        ]);
    }

    public function tokenView(Project $project)
    {
        $this->authorize('viewProjectToken', $project);

        $token = $project->tokens()->first();

        return redirect()->route('projects.show', [$project])->with('flash', [
            'token' => $token->token_plain_part,
        ]);
    }
}
