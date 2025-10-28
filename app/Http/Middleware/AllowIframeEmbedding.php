<?php

namespace App\Http\Middleware;

use Closure;

class AllowIframeEmbedding
{
    public function handle($request, Closure $next)
    {
        $allowedOrigins = [
            'https://simplestats.io',
            'https://simplestats.test',
        ];

        // Adjust session cookie settings for cross-origin iframe embedding
        $this->adjustSessionCookiesForIframe($request, $allowedOrigins);

        $response = $next($request);

        $response->headers->remove('X-Frame-Options');

        $csp = "frame-ancestors 'self' ".implode(' ', $allowedOrigins);

        $response->headers->set('Content-Security-Policy', $csp);

        return $response;
    }

    /**
     * Adjust session cookie settings when loaded in an iframe from allowed origins
     */
    protected function adjustSessionCookiesForIframe($request, array $allowedOrigins): void
    {
        $origin = $request->header('Origin');
        $referer = $request->header('Referer');

        // Check if request comes from an allowed origin (iframe context)
        $isIframeContext = false;
        foreach ($allowedOrigins as $allowedOrigin) {
            if ($origin === $allowedOrigin || str_starts_with($referer ?? '', $allowedOrigin)) {
                $isIframeContext = true;
                break;
            }
        }

        // Adjust session cookie settings for cross-origin iframe
        if ($isIframeContext) {
            config([
                'session.same_site' => 'none',
                'session.secure' => true,
                'session.partitioned' => true,
            ]);
        }
    }
}
