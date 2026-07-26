<?php

namespace App\Http\Controllers;

use App\Models\Team;
use App\Services\Ai\TeamAiConfig;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Response;
use Laravel\Jetstream\Actions\ValidateTeamDeletion;
use Laravel\Jetstream\Contracts\DeletesTeams;
use Laravel\Jetstream\Contracts\UpdatesTeamNames;
use Laravel\Jetstream\Jetstream;
use Laravel\Jetstream\RedirectsActions;

/**
 * Replaces Jetstream's vendor routes for {team}: the vendor controllers
 * findOrFail() the raw route parameter as a primary key, which breaks with
 * the public uuid route key. These actions bind the Team implicitly and
 * otherwise mirror the vendor behavior.
 */
class TeamController extends Controller
{
    use RedirectsActions;

    public function show(Request $request, Team $team): Response
    {
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
                ->select('id', 'uuid', 'name', 'domain', 'events_count_monthly')
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

    public function update(Request $request, Team $team): RedirectResponse
    {
        app(UpdatesTeamNames::class)->update($request->user(), $team, $request->all());

        return back(303);
    }

    public function destroy(Request $request, Team $team): RedirectResponse
    {
        app(ValidateTeamDeletion::class)->validate($request->user(), $team);

        $deleter = app(DeletesTeams::class);

        $deleter->delete($team);

        return $this->redirectPath($deleter);
    }
}
