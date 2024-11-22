<?php

namespace App\Http\Controllers;

use App\Services\SubscriptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;

class HomeController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, SubscriptionService $subscriptionService)
    {
        return Inertia::render('Home/LandingPage', [
            'plans' => $subscriptionService->getFrontendPlans(),
            'banners' => collect([
                $subscriptionService->getDiscount(),
            ])->filter(),
        ]);
    }
}
