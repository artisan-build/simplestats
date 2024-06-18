<?php

namespace App\Http\Controllers;

use App\DTOs\StatsFilterDto;
use App\Http\Requests\StatsFilterRequest;
use App\Services\StatsAggregationsService;
use Illuminate\Http\Request;

class StatsGroupedController extends Controller
{
    public function __invoke(StatsFilterRequest $request, StatsAggregationsService $statsAggregationsService)
    {
        return $statsAggregationsService->getAggregatedGroupedData(
            StatsFilterDto::fromRequest($request),
            $request->groupType,
        );
    }
}
