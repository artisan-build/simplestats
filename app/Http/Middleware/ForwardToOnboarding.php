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
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $currentTeam = $request->user()?->currentTeam;

        if (Str::of($request->getRequestUri())->contains(RouteServiceProvider::HOME)) {
            // indicator that team onboarding is not finished
            if (! $currentTeam->currency) {
                return redirect()->route('teams.edit', $currentTeam);
            } elseif ($currentTeam->instances()->count() < 1) {
                //return redirect()->route('intro');
            }
        }

        return $next($request);
    }
}
