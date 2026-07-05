<?php

namespace App\Http\Controllers;

use App\Services\BlogService;
use App\Services\SubscriptionService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class HomeController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, SubscriptionService $subscriptionService, BlogService $blogService)
    {
        $discount = $subscriptionService->getDiscount();

        return Inertia::render('Home/LandingPage', [
            'plans' => $subscriptionService->getFrontendPlans(),
            'banners' => collect([
                $discount,
            ])->filter(),
            'discount' => $discount['percent'] ?? 0,
            'latestPosts' => $blogService->latestPostCards(3),
        ]);
    }
}
