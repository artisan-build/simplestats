<?php

namespace App\Http\Controllers;

use App\Services\Ai\TeamAiConfig;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Response;
use Laravel\Jetstream\Jetstream;

class TeamController extends Controller
{
    public function show(Request $request, int $teamId): Response
    {
        $team = Jetstream::newTeamModel()->findOrFail($teamId);

        Gate::authorize('view', $team);

        return Jetstream::inertia()->render($request, 'Teams/Show', [
            'team' => $team->load('owner', 'users', 'teamInvitations'),
            'availableRoles' => array_values(Jetstream::$roles),
            'availablePermissions' => Jetstream::$permissions,
            'defaultPermissions' => Jetstream::$defaultPermissions,
            'permissions' => [
                'canAddTeamMembers' => Gate::check('addTeamMember', $team),
                'canDeleteTeam' => Gate::check('delete', $team),
                'canRemoveTeamMembers' => Gate::check('removeTeamMember', $team),
                'canUpdateTeam' => Gate::check('update', $team),
                'canUpdateTeamMembers' => Gate::check('updateTeamMember', $team),
            ],
            'projectsEventUsage' => $team->projects()
                ->select('id', 'name', 'domain', 'events_count_monthly')
                ->orderByDesc('events_count_monthly')
                ->get(),
            'aiSettings' => [
                'provider' => $team->ai_provider,
                'model' => $team->ai_model,
                'keySet' => $team->ai_api_key !== null,
                'availableProviders' => TeamAiConfig::allowedProviders(),
            ],
        ]);
    }
}
