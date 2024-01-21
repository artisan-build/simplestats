<?php

namespace App\Http\Controllers;

use App\Services\StatsOverviewService;
use App\Services\StatsProjectService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class StatsProjectController extends Controller
{
    public function __invoke(StatsProjectService $statsProjectService)
    {
        $project = $statsProjectService->getStats([
            'range_type' => 'days',
            'range_amount' => 7,
            'range_start' => now(),
            'instance_id' => null,
        ]);
dd($project);
        return Inertia::render('Stats/Show', compact('project'));
    }
}
