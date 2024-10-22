<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StatsPaymentRequest;
use App\Jobs\TrackStatsPayment;
use App\Models\StatsPayment;
use App\Services\StatsService;
use Illuminate\Http\JsonResponse;

class StatsPaymentController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(StatsPayment::class, 'stats_payment');
    }

    public function store(StatsPaymentRequest $request): JsonResponse
    {
        if (! auth()->user()->project->hasTrackingUsageBits([StatsService::TRACKING_USAGE_BITS_PAYMENTS])) {
            return response()->json(['message' => 'Tracking Usage not set'], 200);
        }

        TrackStatsPayment::dispatch(
//            instanceId: auth()->user()->id,
            input: $request->validated(),
        );

        return response()->json(['message' => 'Stats Payment Request accepted.']);
    }
}
