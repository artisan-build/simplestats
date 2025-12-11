<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StatsUserRequest;
use App\Jobs\TrackStatsUser;
use App\Models\StatsUser;
use hisorange\BrowserDetect\Facade as Browser;
use Illuminate\Http\JsonResponse;
use function Illuminate\Support\defer;

class StatsUserController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(StatsUser::class, 'stats_user');
    }

    public function store(StatsUserRequest $request): JsonResponse
    {
        if (Browser::parse(urldecode($request->user_agent))->isBot()) {
            // we respond, cause else the client would retry these requests again and again if they use queues...
            return response()->json(['message' => 'Bots are not allowed.']);
        }

        defer(fn () => TrackStatsUser::dispatch(
            input: $request->validated(),
        ));

        return response()->json(['message' => 'Stats User Request accepted.']);
    }
}
