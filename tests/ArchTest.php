<?php

use App\Http\Controllers\Api\StatsReadController;
use App\Http\Controllers\UserProfileController;
use App\Jobs\TrackStatsLogin;
use App\Services\StatsAggregationsService;
use App\Services\StatsService;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;

arch()->preset()->php()->ignoring('SimpleStatsIo');

// md5 only builds cache keys; UserProfileController unserializes the app's own session payloads
arch()->preset()->security()->ignoring([
    'SimpleStatsIo',
    'Database\Seeders',
    'md5',
    UserProfileController::class,
]);

arch('debugging functions are not used')
    ->expect(['dd', 'ddd', 'dump', 'ray', 'var_dump'])
    ->not->toBeUsedIn(['App', 'Database']);

arch('env() is only used inside config files')
    ->expect('env')
    ->not->toBeUsedIn(['App', 'Database']);

arch('mutable Carbon is not used')
    ->expect(Carbon::class)
    ->not->toBeUsedIn(['App', 'Database'])
    ->ignoring([StatsAggregationsService::class, StatsService::class, TrackStatsLogin::class]);

arch('controllers have no private helpers')
    ->expect('App\Http\Controllers')
    ->not->toHavePrivateMethods()
    ->ignoring(StatsReadController::class);

arch('enums are enums')
    ->expect('App\Enums')
    ->toBeEnums();

arch('concerns are traits')
    ->expect('App\Concerns')
    ->toBeTraits();

arch('form requests extend FormRequest')
    ->expect('App\Http\Requests')
    ->toExtend(FormRequest::class);
