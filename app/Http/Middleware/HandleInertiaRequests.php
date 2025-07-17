<?php

namespace App\Http\Middleware;

use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Number;
use Inertia\Middleware;
use Tightenco\Ziggy\Ziggy;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     * @param  \Illuminate\Http\Request  $request
     * @return string|null
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Defines the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function share(Request $request): array
    {
        $merge = [
            'canLogin' => Route::has('login'),
            'canRegister' => Route::has('register'), // this gets deactived through the fortify-config
            'branding' => config('app.branding'),
            'appType' => config('app.app_type'),
            'ziggy' => (new Ziggy)->toArray(),
        ];

        if (auth()->user()) {
            if ($currentTeam = auth()->user()->currentTeam) {
                $merge['projectsCount'] = $currentTeam->projects()->count();
                $merge['onboardingPassed'] = $currentTeam->instances()->count() > 0;
                $merge['teamCurrency'] = $currentTeam->currency;
            }
        }

        return array_merge(parent::share($request), $merge);
    }
}
