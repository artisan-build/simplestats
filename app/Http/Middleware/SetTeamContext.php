<?php

namespace App\Http\Middleware;

use App\Helpers\AppHelper;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Context;
use Symfony\Component\HttpFoundation\Response;

class SetTeamContext
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        AppHelper::setTeamContextFromAuth($request->user());

        return $next($request);
    }
}
