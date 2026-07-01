<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StatsCustomPropertyRequest;
use App\Jobs\TrackStatsCustomProperties;
use Illuminate\Http\JsonResponse;

use function Illuminate\Support\defer;

class StatsCustomPropertyController extends Controller
{
    public function store(StatsCustomPropertyRequest $request): JsonResponse
    {
        defer(fn () => TrackStatsCustomProperties::dispatch(
            input: $request->validated(),
        ));

        return response()->json([
            'message' => 'Stats Custom Property Request accepted.',
            ...$request->validated('visitor_hash')
                ? ['visitor_hash' => $request->validated('visitor_hash')]
                : ['id' => $request->validated('stats_user_id')],
        ]);
    }
}
