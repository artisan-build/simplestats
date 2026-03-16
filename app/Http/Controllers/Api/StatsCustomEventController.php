<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StatsCustomEventRequest;
use App\Jobs\TrackStatsCustomEvent;
use App\Models\StatsCustomEvent;
use Illuminate\Http\JsonResponse;
use function Illuminate\Support\defer;

class StatsCustomEventController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(StatsCustomEvent::class, 'stats_custom_event');
    }

    public function store(StatsCustomEventRequest $request): JsonResponse
    {
        defer(fn () => TrackStatsCustomEvent::dispatch(
            input: $request->validated(),
        ));

        return response()->json(['message' => 'Stats Custom Event Request accepted.']);
    }
}
