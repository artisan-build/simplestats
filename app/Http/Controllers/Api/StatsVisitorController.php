<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StatsVisitorRequest;
use App\Jobs\TrackStatsVisitor;
use App\Models\StatsVisitor;
use App\Services\BotDetectionService;
use Illuminate\Http\JsonResponse;
use function Illuminate\Support\defer;

class StatsVisitorController extends Controller
{
    /**
     * as long as you are allowed to track stats logins,
     * you are allowed to track stats visitors
     */
    public function __construct()
    {
        $this->authorizeResource(StatsVisitor::class, 'stats_visitor');
    }

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
