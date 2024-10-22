<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StatsVisitorRequest;
use App\Jobs\TrackStatsVisitor;
use App\Models\StatsLogin;
use App\Models\StatsVisitor;
use App\Services\StatsService;
use Carbon\Carbon;
use hisorange\BrowserDetect\Facade as Browser;
use Illuminate\Http\JsonResponse;

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
        if (Browser::parse(urldecode($request->user_agent))->isBot()) {
            // we respond, cause else the client would retry these requests again and again if they use queues...
            return response()->json(['message' => 'Bots are not allowed'], 200);
        }

        if (! auth()->user()->project->hasTrackingUsageBits([StatsService::TRACKING_USAGE_BITS_VISITORS])) {
            return response()->json(['message' => 'Tracking Usage not set'], 200);
        }

        TrackStatsVisitor::dispatch(
//            instanceId: auth()->user()->id,
            input: $request->validated(),
        );

        return response()->json(['message' => 'Stats Visitor Request accepted.'], 200);
    }
}
