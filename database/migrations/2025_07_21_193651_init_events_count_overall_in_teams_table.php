<?php

use App\Models\StatsLogin;
use App\Models\StatsPayment;
use App\Models\StatsUser;
use App\Models\StatsVisitor;
use App\Models\Team;
use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Team::all()->each(function (Team $team) {
            $now = Carbon::now()->setTimezone($team->timezone ?? config('app.timezone'));
            $startOfMonth = $now->copy()->startOfMonth();
            $endOfMonth = $now->copy()->endOfMonth();

            $monthlyEvents = 0 +
                StatsVisitor::where('team_id', $team->id)
                    ->whereBetween('time', [$startOfMonth, $endOfMonth])
                    ->count() +
                StatsUser::where('team_id', $team->id)
                    ->whereBetween('time', [$startOfMonth, $endOfMonth])
                    ->count() +
                StatsLogin::where('team_id', $team->id)
                    ->whereBetween('time', [$startOfMonth, $endOfMonth])
                    ->count() +
                StatsPayment::where('team_id', $team->id)
                    ->whereBetween('time', [$startOfMonth, $endOfMonth])
                    ->count();

            $team->update([
                'events_count_monthly' => $monthlyEvents,
                'events_count_overall' => $monthlyEvents,
            ]);
        });
    }
};
