<?php

namespace App\Http\Controllers;

use App\Http\DataProviders\RecurringRevenueDataProvider;
use App\Http\DataProviders\StatsGroupsDataProvider;
use App\Http\DataProviders\StatsIndexDataProvider;
use Illuminate\Http\Request;
use Inertia\Inertia;

class StatsController extends Controller
{
    public function index(StatsIndexDataProvider $dataProvider)
    {
        return Inertia::render('Dashboard/Index', $dataProvider->toArray());
    }

    public function recurringRevenue(RecurringRevenueDataProvider $dataProvider)
    {
        return Inertia::render('RecurringRevenue/Index', $dataProvider->toArray());
    }

    public function groups(StatsGroupsDataProvider $dataProvider): array
    {
        return $dataProvider->toArray();
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
