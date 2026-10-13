<?php

namespace App\Http\Middleware;

use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Arr;
use Inertia\Inertia;
use Laravel\Fortify\Features;
use Laravel\Jetstream\Http\Middleware\ShareInertiaData as JetstreamShareInertiaData;
use Laravel\Jetstream\Jetstream;

/**
 * Replaces Jetstream's share of the full user model (which serializes every
 * loaded relation: teams with owners, subscriptions, presets, settings) with a
 * curated payload of exactly what the frontend reads. Bound over the vendor
 * class in AppServiceProvider.
 */
class ShareInertiaData extends JetstreamShareInertiaData
{
    public function handle($request, $next)
    {
        return parent::handle($request, function ($request) use ($next) {
            Inertia::share([
                'auth' => [
                    'user' => fn (): ?array => $this->serializeUser($request->user()),
                ],
            ]);

            return $next($request);
        });
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function serializeUser(?User $user): ?array
    {
        if ($user === null) {
            return null;
        }

        $teamFields = fn (Team $team): array => ['uuid' => $team->uuid, 'name' => $team->name];

        return [
            'uuid' => $user->uuid,
            'name' => $user->name,
            'email' => $user->email,
            'email_verified_at' => $user->email_verified_at,
            'profile_photo_url' => $user->profile_photo_url,
            'settings' => Arr::only($user->settings ?? [], ['selectedGroupTypes', 'propertyLifetime']),
            'current_team_uuid' => $user->currentTeam?->uuid,
            'current_team' => $user->currentTeam ? $teamFields($user->currentTeam) : null,
            'all_teams' => Jetstream::userHasTeamFeatures($user)
                ? $user->allTeams()->map($teamFields)->values()
                : [],
            'two_factor_enabled' => Features::enabled(Features::twoFactorAuthentication())
                && ! is_null($user->two_factor_secret),
            'has_passkeys' => Features::canManagePasskeys() && $user->hasPasskeysEnabled(),
        ];
    }
}
