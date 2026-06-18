<?php

namespace App\Http\Controllers;

use App\DTOs\StatsFilterDto;
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

    public function groups(StatsFilterRequest $request, StatsAggregationsService $statsAggregationsService): array
    {
        if (isset($request->group_type)) {
            // the "property" group remembers the selected dimension (a
            // property name id), all other groups the selected stats type
            $request->user()->updateSettings(
                "selectedGroupTypes.$request->project_id.$request->group_type",
                $request->stats_type === 'property' ? (int) $request->property_name_id : $request->stats_type,
            );
        }

        $filters = StatsFilterDto::fromRequest($request);

        $response = [
            'data' => $this->fetchGroupedData($statsAggregationsService, $request, $filters),
        ];

        if ($filters->shouldCompare()) {
            $response['data_previous'] = $this->fetchGroupedData(
                $statsAggregationsService,
                $request,
                StatsService::createPrevFiltersForComparison($filters),
            );
        }

        return $response;
    }

    /**
     * Fetch one period of grouped rows. "property" groups by the values of
     * the selected custom property, everything else by a static dimension.
     *
     * ->values()->toArray() makes sure the indexes are int-numeric and not
     * string-numeric (which would result in a js object). This happens cause
     * of the reject at the end of the aggregation methods.
     *
     * @return array<int, array<string, mixed>>
     */
    private function fetchGroupedData(StatsAggregationsService $statsAggregationsService, StatsFilterRequest $request, StatsFilterDto $filters): array
    {
        $limit = $request->limit ?? 5; // must be equal to Load more in StatsGroupTable.vue

        if ($request->stats_type === 'property') {
            return $statsAggregationsService->getAggregatedPropertyData(
                $filters,
                (int) $request->property_name_id,
                $request->stats_sort ?? 'reg',
                $limit,
                $request->boolean('lifetime'),
            )->values()->toArray();
        }

        return $statsAggregationsService->getAggregatedGroupedData(
            $filters,
            $request->stats_type,
            $request->stats_sort,
            $limit,
        )->values()->toArray();
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
