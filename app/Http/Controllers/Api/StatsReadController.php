<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\DataProviders\Api\ApiStatsGroupedDataProvider;
use App\Http\DataProviders\Api\ApiStatsIndexDataProvider;
use App\Services\StatsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class StatsReadController extends Controller
{
    public function index(ApiStatsIndexDataProvider $dataProvider): JsonResponse
    {
        return response()->json($dataProvider->toArray());
    }

    public function grouped(Request $request, ApiStatsGroupedDataProvider $dataProvider): JsonResponse
    {
        $request->validate([
            'stats_type' => ['required'],
        ]);

        return response()->json($dataProvider->toArray());
    }

    public function groups(Request $request): JsonResponse
    {
        $request->validate([
            'stats_type' => [
                'required',
                'string',
                Rule::in(StatsService::getStatsTypesFlat()->toArray()),
            ],
        ]);

        $statsType = StatsService::getStatsTypesFlatMap()
            ->firstWhere('type', $request->stats_type);

        $data = $statsType['model']::query()
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        return response()->json([
            'data' => $data,
        ]);
    }
}
