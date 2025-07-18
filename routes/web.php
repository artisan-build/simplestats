<?php

use App\Http\Controllers\SetupInstallController;
use App\Http\Controllers\SetupProjectController;
use App\Http\Controllers\SetupTeamController;
use App\Models\Team;
use Illuminate\Http\Request;
use App\Http\Controllers\DataImportController;
use App\Http\Controllers\DocsController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ImprintController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ProjectInstanceController;
use App\Http\Controllers\StatsController;
use App\Http\Controllers\TimezoneController;
use App\Http\Controllers\UserNotificationController;
use App\Http\Controllers\UserSettingsController;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Spatie\Health\Http\Controllers\HealthCheckResultsController;

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
Route::resource('timezones', TimezoneController::class)->only(['index']);

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::middleware(['admin'])->group(function () {
        Route::get('health', HealthCheckResultsController::class);
    });

    Route::impersonate();

    Route::resource('setup/team', SetupTeamController::class)->only(['index', 'store'])->names('setup.team');
    Route::resource('setup/project', SetupProjectController::class)->only(['index', 'store'])->names('setup.project');
    Route::resource('setup/installation', SetupInstallController::class)->only(['index'])->names('setup.installation');

    Route::get('dashboard', [StatsController::class, 'index'])->name('dashboard');
    Route::get('stats/grouped', [StatsController::class, 'groups'])->name('stats.grouped');
    Route::post('stats/set-series', [StatsController::class, 'setSeries'])->name('stats.set-series');
    Route::post('stats/hide-free-plan-warning', [StatsController::class, 'hideFreePlanWarning'])->name('stats.hide-free-plan-warning');

    // Data Import
    Route::resource('data-import', DataImportController::class)->only(['index', 'store']);
    Route::post('data-import/{data_import}/cancel', [DataImportController::class, 'cancel'])->name('data-import.cancel');
    Route::post('data-import/{data_import}/rollback', [DataImportController::class, 'rollback'])->name('data-import.rollback');

    // Projects
    Route::resource('projects', ProjectController::class)->except(['edit']);

    // Instances
    Route::resource('projects.instances', ProjectInstanceController::class)->except(['create', 'edit']);
    Route::get('projects/{project}/instances/{instance}/token-view', [ProjectInstanceController::class, 'tokenView'])->name('projects.instances.token-view');
    Route::get('projects/{project}/instances/{instance}/token-regenerate', [ProjectInstanceController::class, 'tokenRegenerate'])->name('projects.instances.token-regenerate');

    // User Settings
    Route::get('user-settings/toggle-menu', [UserSettingsController::class, 'toggleMenu'])->name('user-settings.toggle-menu');
    Route::get('user-settings/toggle-advanced', [UserSettingsController::class, 'toggleAdvanced'])->name('user-settings.toggle-advanced');

    // User Notification Settings
    Route::resource('user/notifications', UserNotificationController::class)->only(['index', 'update']);
    Route::post('user/notifications/{notification}/trigger', [UserNotificationController::class, 'trigger'])->name('notifications.trigger');
});

require __DIR__.'/socialstream.php';
