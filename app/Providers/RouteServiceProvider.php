<?php

namespace App\Providers;

use App\Models\Project;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to your application's "home" route.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    public const HOME = '/dashboard';

    private const API_RATE_LIMIT_DEFAULT = 200;

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function boot(): void
    {
        RateLimiter::for('api', function (Request $request) {
            $limit = self::API_RATE_LIMIT_DEFAULT;
            $key = $request->ip();

            $project = $request->user();

            if ($project instanceof Project) {
                $plan = $project->team->owner->sparkPlan();
                $limit = $plan->options['ApiRateLimit'] ?? self::API_RATE_LIMIT_DEFAULT;
                $key = 'project:'.$project->id;
            }

            return Limit::perMinute($limit)->by($key);
        });

        $this->routes(function () {
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
    }
}
