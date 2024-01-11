<?php

use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
//    Route::get('/user', function (Request $request) {
//        return $request->user();
//    });

    Route::apiResource('stats-login', \App\Http\Controllers\Api\StatsLoginController::class)->only(['store']);
    Route::apiResource('stats-user', \App\Http\Controllers\Api\StatsUserController::class)->only(['store']);
    Route::apiResource('stats-payment', \App\Http\Controllers\Api\StatsPaymentController::class)->only(['store']);
});
