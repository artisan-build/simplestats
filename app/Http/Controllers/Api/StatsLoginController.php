<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StatsLoginRequest;
use App\Jobs\TrackStatsLogin;
use Illuminate\Http\JsonResponse;

use function Illuminate\Support\defer;

class StatsLoginController extends Controller
{
    public function store(StatsLoginRequest $request): JsonResponse
    {
        defer(fn () => TrackStatsLogin::dispatch(
            input: $request->validated(),
        ));

        return response()->json([
            'message' => 'Stats Login Request accepted.',
            'id' => $request->validated('stats_user_id'),
        ]);
    }
}
