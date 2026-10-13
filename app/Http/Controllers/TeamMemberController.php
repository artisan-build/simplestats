<?php

namespace App\Http\Controllers;

use App\Models\Team;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Laravel\Jetstream\Actions\UpdateTeamMemberRole;
use Laravel\Jetstream\Contracts\AddsTeamMembers;
use Laravel\Jetstream\Contracts\InvitesTeamMembers;
use Laravel\Jetstream\Contracts\RemovesTeamMembers;
use Laravel\Jetstream\Features;

/**
 * Replaces Jetstream's vendor routes for {team}: the vendor controllers
 * findOrFail() the raw route parameter as a primary key, which breaks with
 * the public uuid route key. These actions bind the Team implicitly and
 * otherwise mirror the vendor behavior.
 */
class TeamMemberController extends Controller
{
    public function store(Request $request, Team $team): RedirectResponse
    {
        if (Features::sendsTeamInvitations()) {
            resolve(InvitesTeamMembers::class)->invite(
                $request->user(),
                $team,
                $request->email ?: '',
                $request->role
            );
        } else {
            resolve(AddsTeamMembers::class)->add(
                $request->user(),
                $team,
                $request->email ?: '',
                $request->role
            );
        }

        return back(303);
    }

    public function update(Request $request, Team $team, User $user): RedirectResponse
    {
        resolve(UpdateTeamMemberRole::class)->update(
            $request->user(),
            $team,
            $user->id,
            $request->role
        );

        return back(303);
    }

    public function destroy(Request $request, Team $team, User $user): RedirectResponse
    {
        resolve(RemovesTeamMembers::class)->remove(
            $request->user(),
            $team,
            $user
        );

        if ($request->user()->id === $user->id) {
            return redirect(config('fortify.home'));
        }

        return back(303);
    }
}
