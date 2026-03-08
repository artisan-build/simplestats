<?php

namespace App\Http\Controllers;

use App\Services\CustomEventTrackingService;
use Illuminate\Http\RedirectResponse;

class TrackingController extends Controller
{
    public function getStartedClick(CustomEventTrackingService $customEventTrackingService): RedirectResponse
    {
        $customEventTrackingService->trackGetStartedClick();

        return to_route('register');
    }
}
