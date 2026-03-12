<?php

namespace App\Http\Controllers\Api;

use App\DTOs\StatsFilterDto;
use App\Enums\StatsRangeType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ApiStatsFilterRequest;
use App\Services\StatsAggregationsService;
use App\Services\StatsDetailsService;
use App\Services\StatsService;
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
        return [
            'project_id' => $filters->projectId,
            'range_type' => $filters->rangeType->value,
            'range_amount' => $filters->rangeAmount,
            'range_start' => $filters->rangeStart->format('Y-m-d'),
            'preset' => $filters->preset?->value,
            'comparison' => $filters->comparison,
        ];
    }
}
