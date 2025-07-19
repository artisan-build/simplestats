<?php

namespace App\Http\Middleware\Api;

use Closure;
use Illuminate\Http\Request;

class CheckClientVersion
{
    public function handle(Request $request, Closure $next)
    {
        // as they authorize with the auth token from the instance, which uses the HasApiToken trait, the user ist the instance...
        $instance = auth()->user();

        if ($request->hasHeader('X-SimpleStats-Client-Version') && $request->header('X-SimpleStats-Client-Version') != $instance->client_version) {
            $instance->update(['client_version' => $request->header('X-SimpleStats-Client-Version')]);
        }

        return $next($request);
    }
}
