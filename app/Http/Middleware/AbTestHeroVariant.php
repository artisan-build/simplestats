<?php

namespace App\Http\Middleware;

use App\Http\Controllers\HomeController;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cookie;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class AbTestHeroVariant
{
    private const string COOKIE = 'hero_variant';

    /**
     * Active hero A/B variants. Must mirror the keys in Hero.vue's `variants`.
     * Paused variants (analytics_meets_revenue, campaigns_make_money) are intentionally
     * left out so they never get assigned.
     *
     * @var list<string>
     */
    private const array VARIANTS = ['control', 'visitor_to_revenue', 'outcome_rotation'];

    private const TTL_MINUTES = 60 * 24 * 365;

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->rendersHero($request)) {
            return $next($request);
        }

        $isProduction = app()->isProduction();

        // Outside production we ignore any stored cookie and re-randomize on every
        // request, so a reload cycles through the variants for easy eyeballing.
        $variant = $isProduction ? $request->cookie(self::COOKIE) : null;

        if (! in_array($variant, self::VARIANTS, true)) {
            $variant = Arr::random(self::VARIANTS);

            // Make it readable within THIS request so the visitor custom-property
            // resolver (runs in CheckTracking, later in the pipeline) tags the same
            // value on the very first visit, before the cookie round-trips.
            $request->cookies->set(self::COOKIE, $variant);

            if ($isProduction) {
                Cookie::queue(self::COOKIE, $variant, self::TTL_MINUTES);
            }
        }

        Inertia::share('heroVariant', $variant);

        return $next($request);
    }

    private function rendersHero(Request $request): bool
    {
        if (! $request->isMethod('GET')) {
            return false;
        }

        // Only the public landing page renders the hero. Both the English and the
        // localized "/" routes resolve to HomeController.
        $action = $request->route()?->getActionName();

        return is_string($action) && str_starts_with($action, HomeController::class);
    }
}
