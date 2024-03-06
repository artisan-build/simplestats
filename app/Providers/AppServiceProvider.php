<?php

namespace App\Providers;

use App\Enums\DataImportType;
use App\Imports\BaseStatsImport;
use App\Models\DataImport;
use App\Models\PersonalAccessToken;
use Carbon\CarbonImmutable;
use Date;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\Sanctum;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Sanctum::$personalAccessTokenModel = PersonalAccessToken::class;

        Date::use(CarbonImmutable::class);
        Model::preventLazyLoading(! app()->isProduction());
        Model::shouldBeStrict(! app()->isProduction());

//        ray()->clearScreen();
    }
}
