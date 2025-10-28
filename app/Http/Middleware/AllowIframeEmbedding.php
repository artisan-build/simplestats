<?php

namespace App\Http\Middleware;

use Closure;

class AllowIframeEmbedding
{
    public function handle($request, Closure $next)
    {
        $response = $next($request);

        $response->headers->remove('X-Frame-Options');

        $allowedOrigins = [
            'https://simplestats.io',
            'http://simplestats.test',
            'http://localhost:8000',
        ];

        $csp = "frame-ancestors 'self' ".implode(' ', $allowedOrigins);

        $response->headers->set('Content-Security-Policy', $csp);

        return $response;
    }
}
