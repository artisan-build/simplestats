<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Carbon\Carbon;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $projects = Project::orderBy('created_at', 'desc')->with([
            'instances' => function (Builder $query) {
                $query->withCount([
                    'statsusers',
                    'statsusers as statsusers_count_yesterday' => function (Builder $query) {
                        $query->whereDate('created_at', Carbon::yesterday());
                    },
                ]);
            }
        ])->get();

        return Inertia::render('Dashboard', compact('projects'));
    }
}
