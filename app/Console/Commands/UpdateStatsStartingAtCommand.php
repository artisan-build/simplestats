<?php

namespace App\Console\Commands;

use App\Services\StatsService;
use Illuminate\Console\Command;

class UpdateStatsStartingAtCommand extends Command
{
    protected $signature = 'app:recalc-stats-starting-at';

    protected $description = 'Updates the value, when the stats for teams and projects starts';

    public function handle(StatsService $statsService): void
    {
        $statsService->recalculateStatsStartingAt();
    }
}
