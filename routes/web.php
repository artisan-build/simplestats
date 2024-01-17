<?php

use App\Http\Controllers\StatsController;
use App\Http\Controllers\StatsProjectController;
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
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
    ]);
});

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/stats', StatsController::class)->name('stats');
    Route::get('/stats/projects/{project}', StatsProjectController::class)->name('stats.projects');

    // Projects
    Route::resource('projects', App\Http\Controllers\ProjectController::class);
//    Route::get('projects/{project}/stats', [App\Http\Controllers\ProjectController::class, 'stats'])->name('projects.stats');

    // Instances
    Route::resource('projects.instances', App\Http\Controllers\ProjectInstanceController::class);
    Route::post('projects/{project}/instances/{instance}/token-view', [App\Http\Controllers\ProjectInstanceController::class, 'tokenView'])->name('projects.instances.token-view');
    Route::post('projects/{project}/instances/{instance}/token-regenerate', [App\Http\Controllers\ProjectInstanceController::class, 'tokenRegenerate'])->name('projects.instances.token-regenerate');
});
