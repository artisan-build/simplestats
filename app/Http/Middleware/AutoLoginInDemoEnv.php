<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AutoLoginInDemoEnv
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Only auto-login in demo environment
        if (app()->environment('demo')) {
            if (! auth()->check()) {
                $testUser = User::where('email', config('app.test.user.email'))->first();

                if ($testUser) {
                    auth()->login($testUser);

                    return to_route('dashboard');
                }
            } elseif ($request->is('/')) {
                return to_route('dashboard');
            }
        }

        return $next($request);
    }
}
