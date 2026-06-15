<?php

namespace App\Providers;

use App\Models\PersonalAccessToken;
use App\Models\StatsUser;
use App\Models\StatsVisitor;
use App\Models\User;
use App\Observers\UserObserver;
use App\PulseUsers;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Laravel\Pulse\Contracts\ResolvesUsers;
use Laravel\Sanctum\Sanctum;
use Spatie\Health\Checks\Checks\CacheCheck;
use Spatie\Health\Checks\Checks\DatabaseCheck;
use Spatie\Health\Checks\Checks\DebugModeCheck;
use Spatie\Health\Checks\Checks\EnvironmentCheck;
use Spatie\Health\Checks\Checks\HorizonCheck;
use Spatie\Health\Checks\Checks\RedisCheck;
use Spatie\Health\Checks\Checks\RedisMemoryUsageCheck;
use Spatie\Health\Checks\Checks\ScheduleCheck;
use Spatie\Health\Checks\Checks\UsedDiskSpaceCheck;
use Spatie\Health\Facades\Health;
use Spatie\OgImage\Facades\OgImage;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        if ($this->app->environment('local')) {
            $this->app->register(\Laravel\Telescope\TelescopeServiceProvider::class);
            $this->app->register(TelescopeServiceProvider::class);
        }

        $this->app->singleton(ResolvesUsers::class, PulseUsers::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Health::checks([
            HorizonCheck::new(),
            DatabaseCheck::new(),
            CacheCheck::new(),
            RedisCheck::new(),
            RedisMemoryUsageCheck::new(),
            ScheduleCheck::new()->heartbeatMaxAgeInMinutes(5),
            UsedDiskSpaceCheck::new(),
            EnvironmentCheck::new(),
            DebugModeCheck::new(),
        ]);

        User::observe(UserObserver::class);

        Sanctum::$personalAccessTokenModel = PersonalAccessToken::class;

        // Stable, short aliases for polymorphic stats_custom_properties.propertyable_type,
        // so the column stores 'stats_user' / 'stats_visitor' instead of full class
        // names (shorter index values, decoupled from class renames).
        Relation::morphMap([
            'stats_user' => StatsUser::class,
            'stats_visitor' => StatsVisitor::class,
        ]);

        Date::use(CarbonImmutable::class);
        Model::preventLazyLoading(! app()->isProduction());
        Model::shouldBeStrict(! app()->isProduction());
        Model::unguard();

        DB::prohibitDestructiveCommands(
            $this->app->isProduction(),
        );

        Vite::usePrefetchStrategy('aggressive');

        // spatie/laravel-og-image is not installed in the self-hosted build
        // (composer.self-hosted.json), so OgImage must never be called outside
        // this guard. The import above is harmless (use statements don't autoload).
        if (config('app.app_type') === 'saas') {
            OgImage::fallbackUsing(function (Request $request) {
                return view('og-image.fallback', [
                    'title' => config('app.name'),
                    'tagline' => config('app.tagline', 'Revenue attribution analytics tool for Laravel apps'),
                    'appUrl' => str_replace(['https://', 'http://'], '', config('app.url')),
                ]);
            });
        }

        Gate::define('viewPulse', function (User $user) {
            return $user->isAdmin();
        });
    }
}
