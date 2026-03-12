<?php

namespace App\Http\Controllers\Api;

use App\DTOs\StatsFilterDto;
use App\Enums\StatsRangePreset;
use App\Enums\StatsRangeType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ApiStatsFilterRequest;
use App\Services\StatsAggregationsService;
use App\Services\StatsDetailsService;
use App\Services\StatsService;
use Carbon\CarbonInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StatsReadController extends Controller
{
    public function index(
        ApiStatsFilterRequest $request,
        StatsDetailsService $statsDetailsService,
    ): JsonResponse {
        $project = $request->user();
        $filters = StatsFilterDto::fromRequest($request, $project->id);

        $data = $statsDetailsService->getStats($filters);

        $response = [
            'data' => array_values($data),
            'meta' => $this->buildMeta($filters),
        ];

        if ($filters->comparison && $filters->comparison !== '0' && $filters->rangeType !== StatsRangeType::YEAR) {
            $response['data_previous'] = array_values($statsDetailsService->getStats(
                StatsService::createPrevFiltersForComparison($filters),
            ));
        }

        return response()->json($response);
    }

    public function grouped(
        ApiStatsFilterRequest $request,
        StatsAggregationsService $statsAggregationsService,
    ): JsonResponse {
        $request->validate([
            'stats_type' => ['required'],
        ]);

        $project = $request->user();
        $filters = StatsFilterDto::fromRequest($request, $project->id);

        $data = $statsAggregationsService->getAggregatedGroupedData(
            $filters,
            $request->stats_type,
            $request->stats_sort ?? 'visitors',
            $request->limit ?? 10,
        )->values()->toArray();

        return response()->json([
            'data' => $data,
            'meta' => $this->buildMeta($filters),
        ]);
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

    private function buildMeta(StatsFilterDto $filters): array
    {
        return collect((array) $filters)
            ->except('customPresetId')
            ->mapWithKeys(fn ($value, $key) => [Str::snake($key) => match (true) {
                $value instanceof StatsRangeType => $value->value,
                $value instanceof CarbonInterface => $value->format('Y-m-d'),
                $value instanceof StatsRangePreset => $value->value,
                default => $value,
            }])
            ->toArray();
    }
}
