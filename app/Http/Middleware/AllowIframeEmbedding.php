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
            'https://simplestats.test',
        ];

        $csp = "frame-ancestors 'self' ".implode(' ', $allowedOrigins);

        $response->headers->set('Content-Security-Policy', $csp);

        return $response;
    }
}
