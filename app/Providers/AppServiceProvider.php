<?php

namespace App\Providers;

use App\Builder\Drivers\AggregationBuilderTypeMySQL;
use App\Builder\Drivers\AggregationBuilderTypePgSQL;
use App\Builder\Drivers\AggregationBuilderType;
use App\Helpers\AppHelper;
use App\Models\PersonalAccessToken;
use App\Models\User;
use App\Observers\UserObserver;
use App\PulseUsers;
use Carbon\CarbonImmutable;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Laravel\Pulse\Contracts\ResolvesUsers;
use Laravel\Sanctum\Sanctum;

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

        $this->app->singleton(AggregationBuilderType::class, function ($app) {
            return match (AppHelper::getDBDriver()) {
                'mysql' => new AggregationBuilderTypeMySQL,
                'pgsql' => new AggregationBuilderTypePgSQL,
                default => throw new Exception('DB driver not supported'),
            };
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        User::observe(UserObserver::class);

        Sanctum::$personalAccessTokenModel = PersonalAccessToken::class;

        Date::use(CarbonImmutable::class);
        Model::preventLazyLoading(! app()->isProduction());
        Model::shouldBeStrict(! app()->isProduction());

        DB::prohibitDestructiveCommands(
            $this->app->isProduction(),
        );

        Vite::usePrefetchStrategy('aggressive');

        Gate::define('viewPulse', function (User $user) {
            return $user->isAdmin();
        });
    }
}
