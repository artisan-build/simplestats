<?php

use App\Http\Controllers\DocsController;
use App\Http\Controllers\EarlySubscriptionController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\IntroController;
use App\Http\Controllers\OverviewController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

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

Route::get('/', HomeController::class);
Route::get('/docs/{path?}', DocsController::class)->where('path', '.*');
Route::post('early-subscription', EarlySubscriptionController::class)->name('early-subscription');

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::any('intro', IntroController::class)->name('intro');
    Route::get('overview', OverviewController::class)->name('stats.overview');
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    // Data Import
    Route::resource('data-import', App\Http\Controllers\DataImportController::class)->only(['index', 'store']);
    Route::post('data-import/{data_import}/cancel', [App\Http\Controllers\DataImportController::class, 'cancel'])->name('data-import.cancel');
    Route::post('data-import/{data_import}/rollback', [App\Http\Controllers\DataImportController::class, 'rollback'])->name('data-import.rollback');

    // Projects
    Route::resource('projects', App\Http\Controllers\ProjectController::class);

    // Instances
    Route::resource('projects.instances', App\Http\Controllers\ProjectInstanceController::class);
    Route::get('projects/{project}/instances/{instance}/token-view', [App\Http\Controllers\ProjectInstanceController::class, 'tokenView'])->name('projects.instances.token-view');
    Route::get('projects/{project}/instances/{instance}/token-regenerate', [App\Http\Controllers\ProjectInstanceController::class, 'tokenRegenerate'])->name('projects.instances.token-regenerate');
});
