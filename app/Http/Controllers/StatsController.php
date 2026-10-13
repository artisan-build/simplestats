<?php

namespace App\Http\Controllers;

use App\Http\DataProviders\RevenueDataProvider;
use App\Http\DataProviders\StatsGroupsDataProvider;
use App\Http\DataProviders\StatsIndexDataProvider;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class StatsController extends Controller
{
    public function index(StatsIndexDataProvider $dataProvider)
    {
        return Inertia::render('Dashboard/Index', $dataProvider->toArray());
    }

    public function metric(StatsIndexDataProvider $dataProvider, string $kpi)
    {
        return Inertia::render('Dashboard/Metric', array_merge($dataProvider->toArray(), [
            'kpi' => $kpi,
        ]));
    }

    public function revenue(RevenueDataProvider $dataProvider)
    {
        return Inertia::render('Revenue/Index', $dataProvider->toArray());
    }

    public function groups(StatsGroupsDataProvider $dataProvider): array
    {
        return $dataProvider->toArray();
    }

    public function hideFreePlanWarning(Request $request): RedirectResponse
    {
        $request->user()->updateSettings('hideFreePlanWarning', true);

        return back();
    }

    public function hideReadDocsWarning(Request $request): RedirectResponse
    {
        $request->user()->updateSettings('hideReadDocsWarning', true);

        return back();
    }
}
