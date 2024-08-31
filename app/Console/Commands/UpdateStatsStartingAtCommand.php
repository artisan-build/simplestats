<?php

namespace App\Console\Commands;

use App\Models\Instance;
use App\Services\StatsService;
use Illuminate\Console\Command;

class UpdateStatsStartingAtCommand extends Command
{
    protected $signature = 'app:recalc-stats-starting-at';

    protected $description = 'Updates the value, when the stats for teams, projects and instances starts';

    public function handle(): void
    {
        Instance::all()->each(function (Instance $instance) {
            $start = collect(StatsService::getStatsModels())->map(
                fn ($statsModel) => $statsModel::where(['instance_id' => $instance->id])->min('time')
            )->filter()->min();

            if ($start) {
                $instance->update(['stats_starting_at' => $start]);
            }
        });
    }
}
