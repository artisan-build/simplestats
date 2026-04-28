<?php

namespace App\Http\Controllers;

use App\DTOs\StatsFilterDto;
use App\Enums\StatsRangeType;
use App\Http\DataProviders\StatsIndexDataProvider;
use App\Http\Requests\StatsFilterRequest;
use App\Services\StatsAggregationsService;
use App\Services\StatsService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class StatsController extends Controller
{
    public function index(StatsIndexDataProvider $dataProvider)
    {
        return Inertia::render('Dashboard/Index', $dataProvider->toArray());
    }

    public function groups(StatsFilterRequest $request, StatsAggregationsService $statsAggregationsService)
    {
        if (isset($request->group_type)) {
            $request->user()->updateSettings("selectedGroupTypes.$request->project_id.$request->group_type", $request->stats_type);
        }

        $filters = StatsFilterDto::fromRequest($request);
        $limit = $request->limit ?? 5; // must be equal to Load more in StatsOverviewGroup.vue

        // ->values()->toArray() makes sure the indexes are int-numeric and not string-numeric
        // (which would result in a js object). This happens cause of the reject at the end of "getAggregatedGroupedData"
        $data = $statsAggregationsService->getAggregatedGroupedData(
            $filters,
            $request->stats_type,
            $request->stats_sort,
            $limit,
        )->values()->toArray();

        $response = [
            'data' => $data,
        ];

        if ($filters->comparison && $filters->comparison !== '0' && $filters->rangeType !== StatsRangeType::YEAR) {
            $response['data_previous'] = $statsAggregationsService->getAggregatedGroupedData(
                StatsService::createPrevFiltersForComparison($filters),
                $request->stats_type,
                $request->stats_sort,
                $limit,
            )->values()->toArray();
        }

        return $response;
    }

    public function setSeries(Request $request)
    {
        $chartIndex = $request->chartIndex;
        $seriesIndex = $request->seriesIndex;

        $request->user()->updateSettings("selectedSeries.$chartIndex", $seriesIndex);

        return response()->json();
    }

    public function hideFreePlanWarning(Request $request)
    {
        $request->user()->updateSettings('hideFreePlanWarning', true);

        return back();
    }

    public function hideReadDocsWarning(Request $request)
    {
        $request->user()->updateSettings('hideReadDocsWarning', true);

        return back();
    }
}
