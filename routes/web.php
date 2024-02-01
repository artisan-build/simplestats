<?php

use App\Http\Controllers\EarlySubscriptionController;
use App\Http\Controllers\StatsOverviewController;
use App\Http\Controllers\StatsDetailsController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return Inertia::render('Home/LandingPage', [
        'comingSoon' => config('app.coming_soon'),
        'canLogin' => Route::has('login') && !config('app.coming_soon'),
        'canRegister' => Route::has('register'), // this gets deactived through the fortify-config
    ]);
});

Route::post('early-subscription', EarlySubscriptionController::class)->name('early-subscription');

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/stats/overview', StatsOverviewController::class)->name('stats.overview');
    Route::get('/stats/details', StatsDetailsController::class)->name('stats.details');

    // Projects
    Route::resource('projects', App\Http\Controllers\ProjectController::class);

    // Instances
    Route::resource('projects.instances', App\Http\Controllers\ProjectInstanceController::class);
    Route::get('projects/{project}/instances/{instance}/token-view', [App\Http\Controllers\ProjectInstanceController::class, 'tokenView'])->name('projects.instances.token-view');
    Route::get('projects/{project}/instances/{instance}/token-regenerate', [App\Http\Controllers\ProjectInstanceController::class, 'tokenRegenerate'])->name('projects.instances.token-regenerate');
});
