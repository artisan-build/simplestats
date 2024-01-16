<?php

namespace App\Http\Controllers;

use App\Services\StatsService;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function __invoke(StatsService $statsService)
    {
        $projects = $statsService->getProjectsOverview();

        return Inertia::render('Dashboard/Index', compact('projects'));
    }
}
