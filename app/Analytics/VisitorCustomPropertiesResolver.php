<?php

namespace App\Analytics;

use Illuminate\Http\Request;
use SimpleStatsIo\LaravelClient\Contracts\ResolvesVisitorCustomProperties;

class VisitorCustomPropertiesResolver implements ResolvesVisitorCustomProperties
{
    /**
     * Tag every tracked visitor with the hero A/B variant they were served, so the
     * variant rides along through registration to revenue (see AbTestHeroVariant). The
     * cookie is set in the same request by AbTestHeroVariant, so it is readable here on
     * the very first visit.
     *
     * @return array<string, string>
     */
    public function resolve(Request $request): array
    {
        $variant = $request->cookie('hero_variant');

        if (! is_string($variant) || $variant === '') {
            return [];
        }

        return ['hero_variant' => $variant];
    }
}
