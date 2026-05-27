<?php

use App\Models\Project;
use App\Models\StatsCustomEvent;
use App\Models\StatsLogin;
use App\Models\StatsPayment;
use App\Models\StatsUser;
use App\Models\StatsVisitor;
use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Project::with('team')->get()->each(function (Project $project) {
            $tz = $project->team->timezone ?? config('app.timezone');
            $now = Carbon::now()->setTimezone($tz);
            $startOfMonth = $now->copy()->startOfMonth();
            $endOfMonth = $now->copy()->endOfMonth();

            $monthlyEvents =
                StatsVisitor::where('project_id', $project->id)
                    ->whereBetween('time', [$startOfMonth, $endOfMonth])
                    ->count() +
                StatsUser::where('project_id', $project->id)
                    ->whereBetween('time', [$startOfMonth, $endOfMonth])
                    ->count() +
                StatsLogin::where('project_id', $project->id)
                    ->whereBetween('time', [$startOfMonth, $endOfMonth])
                    ->count() +
                StatsPayment::where('project_id', $project->id)
                    ->whereBetween('time', [$startOfMonth, $endOfMonth])
                    ->count() +
                StatsCustomEvent::where('project_id', $project->id)
                    ->whereBetween('time', [$startOfMonth, $endOfMonth])
                    ->count();

            $project->update(['events_count_monthly' => $monthlyEvents]);
        });
    }
};
