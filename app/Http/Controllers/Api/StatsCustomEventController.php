<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StatsCustomEventRequest;
use App\Jobs\TrackStatsCustomEvent;
use Illuminate\Foundation\Bus\PendingDispatch;
use Illuminate\Http\JsonResponse;

use function Illuminate\Support\defer;

class StatsCustomEventController extends Controller
{
    public function store(StatsCustomEventRequest $request): JsonResponse
    {
        defer(fn (): PendingDispatch => dispatch(new TrackStatsCustomEvent(input: $request->validated())));

        return response()->json([
            'message' => 'Stats Custom Event Request accepted.',
            'id' => $request->validated('id'),
        ]);
    }
}
