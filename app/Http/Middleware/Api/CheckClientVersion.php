<?php

namespace App\Http\Middleware\Api;

use Closure;
use Illuminate\Http\Request;

class CheckClientVersion
{
    public function handle(Request $request, Closure $next)
    {
        // as they authorize with the auth token from the project, which uses the HasApiToken trait, the user ist the project...
        $project = auth()->user();

        if ($request->hasHeader('X-SimpleStats-Client-Version') && $request->header('X-SimpleStats-Client-Version') != $project->client_version) {
            $project->update(['client_version' => $request->header('X-SimpleStats-Client-Version')]);
        }

        return $next($request);
    }
}
