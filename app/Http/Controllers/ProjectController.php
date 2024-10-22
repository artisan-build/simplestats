<?php

namespace App\Http\Controllers;

use App\Actions\CheckPasswordConfirmation;
use App\Http\Requests\ProjectRequest;
use App\Models\Instance;
use App\Models\Project;
use App\Services\StatsAggregationsService;
use App\Services\StatsService;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Fortify\Actions\ConfirmPassword;

class ProjectController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Project::class, 'project');
    }

    public function index(Request $request): Response
    {
        $projects = Project::orderBy('created_at', 'desc')->paginate(10);

        return Inertia::render('Projects/Index', [
            'projects' => $projects,
            'trackingUsages' => StatsService::getTrackingUsages(),
            'permissions' => [
                'canCreateProject' => Gate::check('create', Project::class),
            ]
        ]);
    }

    public function store(ProjectRequest $request): RedirectResponse
    {
        $project = Project::create($request->validated());

        return redirect()->route('projects.show', $project->id)->banner('Project created: '.$project->name);
    }

    public function show(Request $request, Project $project): Response
    {
        $project->load(['instances' => function(HasMany $query) {
            $query->orderBy('created_at', 'DESC');
        }]);

        return Inertia::render('Projects/Show', [
            'project' => $project,
            'trackingUsages' => StatsService::getTrackingUsages(),
            'permissions' => [
                'canDeleteProject' => Gate::check('delete', $project),
                'canUpdateProject' => Gate::check('update', $project),
                'canCreateInstance' => Gate::check('create', Instance::class),
            ],
        ]);
    }

    public function update(ProjectRequest $request, Project $project): RedirectResponse
    {
        $project->update($request->validated());

        return redirect()->back();
    }

    public function destroy(Request $request, Project $project, StatefulGuard $guard, StatsAggregationsService $statsAggregationsService): RedirectResponse
    {
        app(CheckPasswordConfirmation::class)($guard, $request->user(), $request->password);

        $statsAggregationsService->clearByProject($project);

        $project->delete();

        return redirect()->route('projects.index')->banner('Project deleted: '.$project->name);
    }
}
