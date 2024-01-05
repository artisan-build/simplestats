<?php

use App\Http\Controllers\DashboardController;
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
    // Dashboard
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    // Projects
    Route::resource('projects', App\Http\Controllers\ProjectController::class);

    // Instances
    Route::resource('instances', App\Http\Controllers\InstanceController::class);
    Route::post('instances/{instance}/token-view', [App\Http\Controllers\InstanceController::class, 'tokenView'])->name('instances.token-view');
    Route::post('instances/{instance}/token-regenerate', [App\Http\Controllers\InstanceController::class, 'tokenRegenerate'])->name('instances.token-regenerate');
});

Route::get('/test', function () {
    session(['key' => 'foo']);
    dd(session()->all());
});


