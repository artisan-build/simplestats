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
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Only auto-login in demo environment
        if (app()->environment('demo') && ! auth()->check()) {
            $testUser = User::where('email', config('app.test.user.email'))->first();

            if ($testUser) {
                auth()->login($testUser);
                return redirect()->route('dashboard');
            }
        }

        return $next($request);
    }
}
