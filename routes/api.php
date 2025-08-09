<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\StatsLoginController;
use App\Http\Controllers\Api\StatsPaymentController;
use App\Http\Controllers\Api\StatsUserController;
use App\Http\Controllers\Api\StatsVisitorController;

Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    Route::apiResource('stats-visitor', StatsVisitorController::class)->only(['store']);
    Route::apiResource('stats-login', StatsLoginController::class)->only(['store']);
    Route::apiResource('stats-user', StatsUserController::class)->only(['store']);
    Route::apiResource('stats-payment', StatsPaymentController::class)->only(['store']);
});
