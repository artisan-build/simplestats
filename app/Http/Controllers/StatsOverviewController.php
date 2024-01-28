<?php

namespace App\Http\Controllers;

use App\Services\StatsOverviewService;
use Inertia\Inertia;

class StatsOverviewController extends Controller
{
    public function __invoke(StatsOverviewService $statsOverviewService)
    {
        return Inertia::render('Stats/Overview', [
            'teamStats' => $statsOverviewService->getStats(),
        ]);
    }
}
