<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Middleware;
use Tighten\Ziggy\Ziggy;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Defines the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     */
    public function share(Request $request): array
    {
        $merge = [
            'canLogin' => Route::has('login'),
            'canRegister' => Route::has('register'), // this gets deactived through the fortify-config
            'branding' => config('app.branding'),
            'ziggy' => (new Ziggy)->toArray(),
        ];

        if (auth()->user()) {
            $merge['isAdmin'] = auth()->user()->isAdmin();

            if ($currentTeam = auth()->user()->currentTeam) {
                $merge['onboardingPassed'] = $currentTeam->projects()->first()?->name;
                $merge['teamCurrency'] = $currentTeam->currency;
                $merge['currentPlanOptions'] = $currentTeam->owner->sparkPlan()->options;
            }
        }

        return array_merge(parent::share($request), $merge);
    }
}
