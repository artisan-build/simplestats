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
            'statsType' => ['required'],
        ]);

        $project = $request->user();
        $filters = StatsFilterDto::fromRequest($request, $project->id);

        $data = $statsAggregationsService->getAggregatedGroupedData(
            $filters,
            $request->statsType,
            $request->statsSort ?? 'visitors',
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
            'statsType' => [
                'required',
                'string',
                Rule::in(StatsService::getStatsTypesCamelFlat()->toArray()),
            ],
        ]);

        $statsType = StatsService::getStatsTypesCamelFlatMap()
            ->firstWhere('type', $request->statsType);

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
            ->map(fn ($value) => match (true) {
                $value instanceof StatsRangeType => $value->value,
                $value instanceof CarbonInterface => $value->format('Y-m-d'),
                $value instanceof StatsRangePreset => $value->value,
                default => $value,
            })
            ->toArray();
    }
}
