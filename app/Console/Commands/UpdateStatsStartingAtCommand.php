<?php

namespace App\Console\Commands;

use App\Models\Instance;
use App\Models\Project;
use App\Models\Team;
use App\Services\StatsService;
use Illuminate\Console\Command;

class UpdateStatsStartingAtCommand extends Command
{
    protected $signature = 'app:update-stats-starting-at';

    protected $description = 'Updates the value, when the stats for teams, projects and instances starts';

    public function handle(): void
    {
        app(StatsService::class)->recalcStatsStartingAt();
    }
}
