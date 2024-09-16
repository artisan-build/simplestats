<?php

use App\Http\Controllers\DocsController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ImprintController;
use App\Http\Controllers\IntroController;
use App\Http\Controllers\UserSettingsController;
use App\Http\Controllers\StatsController;
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

Route::get('/', HomeController::class)->name('home');
Route::get('/imprint', [ImprintController::class, 'show'])->name('imprint');
Route::get('/docs/{path?}', DocsController::class)->where('path', '.*');

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::impersonate();

    Route::any('intro', IntroController::class)->name('intro');

    Route::get('dashboard', [StatsController::class, 'index'])->name('dashboard');
    Route::get('stats/grouped', [StatsController::class, 'groups'])->name('stats.grouped');
    Route::post('stats/set-series', [StatsController::class, 'setSeries'])->name('stats.set-series');
    Route::post('stats/hide-free-plan-warning', [StatsController::class, 'hideFreePlanWarning'])->name('stats.hide-free-plan-warning');

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

    Route::get('user-settings/toggle-menu', [UserSettingsController::class, 'toggleMenu'])->name('user-settings.toggle-menu');
    Route::get('user-settings/toggle-advanced', [UserSettingsController::class, 'toggleAdvanced'])->name('user-settings.toggle-advanced');
});
