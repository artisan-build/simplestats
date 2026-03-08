<?php

namespace App\Http\Controllers;

use App\Services\CustomEventTrackingService;
use Illuminate\Http\JsonResponse;

class TrackingController extends Controller
{
    public function getStartedClick(CustomEventTrackingService $customEventTrackingService): JsonResponse
    {
        $customEventTrackingService->trackGetStartedClick();

        return response()->json(['tracked' => true]);
    }
}
