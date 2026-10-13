<?php

namespace App\Http\Middleware;

use App\Providers\RouteServiceProvider;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class ForwardToOnboarding
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $currentTeam = $request->user()?->currentTeam;

        if (Str::of($request->getRequestUri())->contains(RouteServiceProvider::HOME)) {
            // we use the currency as the indicator that team onboarding is not finished
            if (! $currentTeam->currency) {
                return redirect()->route('setup.team.index');
            }
            // we use the name as the indicator that project onboarding is not finished
            elseif (! $currentTeam->projects()->first()?->name) {
                return redirect()->route('setup.project.index');
            }
        }

        return $next($request);
    }
}
