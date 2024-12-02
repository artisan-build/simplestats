<?php

namespace App\Console\Commands;

use App\Models\StatsAggregation;
use Illuminate\Console\Command;

class ClearOldStatsAggregations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:clear-old-stats-aggregations';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Deletes all stats aggregations older than 1 month';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        StatsAggregation::where('selected_at', '<', now()->subDays(400))->delete();
    }
}
