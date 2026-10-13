<?php

use App\Http\Controllers\Api\StatsCustomEventController;
use App\Http\Controllers\Api\StatsCustomPropertyController;
use App\Http\Controllers\Api\StatsLoginController;
use App\Http\Controllers\Api\StatsPaymentController;
use App\Http\Controllers\Api\StatsReadController;
use App\Http\Controllers\Api\StatsUserController;
use App\Http\Controllers\Api\StatsVisitorController;
use App\Http\Middleware\Api\EnsureWithinEventLimit;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware('auth:sanctum')->group(function (): void {
    Route::middleware(EnsureWithinEventLimit::class)->group(function (): void {
        Route::apiResource('stats-visitor', StatsVisitorController::class)->only(['store']);
        Route::apiResource('stats-login', StatsLoginController::class)->only(['store']);
        Route::apiResource('stats-user', StatsUserController::class)->only(['store']);
        Route::apiResource('stats-payment', StatsPaymentController::class)->only(['store']);
        Route::apiResource('stats-custom-event', StatsCustomEventController::class)->only(['store']);
        Route::apiResource('stats-custom-properties', StatsCustomPropertyController::class)->only(['store']);
    });

    Route::get('stats', [StatsReadController::class, 'index']);
    Route::get('stats/grouped', [StatsReadController::class, 'grouped']);
    Route::get('stats/groups', [StatsReadController::class, 'groups']);
});
