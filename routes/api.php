<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
//    Route::get('/user', function (Request $request) {
//        return $request->user();
//    });

    Route::apiResource('stats-user', \App\Http\Controllers\Api\StatsUserController::class)->only(['store']);
    Route::apiResource('stats-login', \App\Http\Controllers\Api\StatsLoginController::class)->only(['store']);
});
