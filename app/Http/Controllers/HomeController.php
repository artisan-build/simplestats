<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

class HomeController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        return Inertia::render('Home/LandingPage', [
            'comingSoon' => config('app.coming_soon'),
            'canLogin' => Route::has('login') && !config('app.coming_soon'),
            'canRegister' => Route::has('register'), // this gets deactived through the fortify-config
        ]);
    }
}
