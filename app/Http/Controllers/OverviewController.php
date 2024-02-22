<?php

namespace App\Http\Controllers;

use App\Services\StatsOverviewService;
use Inertia\Inertia;

class OverviewController extends Controller
{
    public function __invoke(StatsOverviewService $statsOverviewService)
    {
        return Inertia::render('Overview/Index', [
            'teamStats' => $statsOverviewService->getStats(),
        ]);
    }
}
