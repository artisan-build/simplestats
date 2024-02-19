<?php

namespace App\Http\Middleware;

use App\Providers\RouteServiceProvider;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ForwardToIntro
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->getRequestUri() === RouteServiceProvider::HOME &&
            $request->user()->currentTeam->instances()->count() < 1
        ) {
            return redirect()->route('intro');
        }

        return $next($request);
    }
}
