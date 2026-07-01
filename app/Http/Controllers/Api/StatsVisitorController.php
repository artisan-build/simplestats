<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StatsVisitorRequest;
use App\Jobs\TrackStatsVisitor;
use App\Services\BotDetectionService;
use Illuminate\Http\JsonResponse;

use function Illuminate\Support\defer;

class StatsVisitorController extends Controller
{
    public function store(StatsVisitorRequest $request): JsonResponse
    {
        if (BotDetectionService::isBot(urldecode($request->user_agent))) {
            // we respond, cause else the client would retry these requests again and again if they use queues...
            return response()->json(['message' => 'Bots are not allowed'], 200);
        }

        defer(fn () => TrackStatsVisitor::dispatch(
            input: $request->validated(),
        ));

        return response()->json([
            'message' => 'Stats Visitor Request accepted.',
            'visitor_hash' => $request->validated('visitor_hash'),
        ]);
    }
}
