<?php

namespace App\Console\Commands;

use App\Models\StatsAggregation;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;

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
    protected $description = 'Deletes stale stats aggregations, purging filtered ones sooner than the unfiltered default view';

    // Kept long so year-over-year comparisons still resolve from cache.
    private const UNFILTERED_RETENTION_DAYS = 400;

    // Filter combinations explode and are rarely revisited, so evict sooner. An
    // actively viewed combination keeps refreshing selected_at and survives anyway;
    // eviction only costs a one-off re-aggregation on the next view.
    private const FILTERED_RETENTION_DAYS = 40;

    public function handle(): void
    {
        // filter IS NULL covers legacy rows from before the column existed.
        StatsAggregation::where(function (Builder $query) {
            $query->whereRaw("json_typeof(filter) = 'array'")->orWhereNull('filter');
        })->where('selected_at', '<', now()->subDays(self::UNFILTERED_RETENTION_DAYS))
            ->delete();

        StatsAggregation::whereRaw("json_typeof(filter) = 'object'")
            ->where('selected_at', '<', now()->subDays(self::FILTERED_RETENTION_DAYS))
            ->delete();
    }
}
