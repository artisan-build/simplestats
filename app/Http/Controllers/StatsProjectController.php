<?php

namespace App\Http\Controllers;

use App\Services\StatsService;
use Inertia\Inertia;

class StatsProjectController extends Controller
{
    public function __invoke(StatsService $statsService)
    {
        $projects = [];
//        $statsService->getProjectsOverview();

        return Inertia::render('Stats/Show', compact('projects'));
    }
}
