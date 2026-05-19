<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureValidTeamOwner
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->currentTeam && ! $user->currentTeam->owner) {
            $fallback = $user->personalTeam() ?? $user->allTeams()->first(fn ($team) => $team->owner);
            if ($fallback) {
                $user->switchTeam($fallback);
            }
        }

        return $next($request);
    }
}
