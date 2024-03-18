<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DisableMutateEndpointsInDemoEnv
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! app()->environment('demo')) {
            return $next($request);
        }

        if ($request->isMethod('get') || $request->is('login') || $request->is('logout') || $request->is('spark/*')) {
            return $next($request);
        }

        return back()->with('flash', [
            'banner' => 'Action not allowed in Demo-App!',
            'bannerStyle' => 'danger',
        ]);
    }
}
