<?php

namespace App\Http\Controllers;

use App\Services\StatsOverviewService;
use Inertia\Inertia;

class StatsController extends Controller
{
    public function __invoke(StatsOverviewService $statsOverviewService)
    {
        $projects = $statsOverviewService->getStats();

        return Inertia::render('Stats/Index', compact('projects'));
    }
}
