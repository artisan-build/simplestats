<?php

namespace App\Observers;

use App\Models\BaseStat;
use App\Models\Project;
use App\Models\Team;
use App\Services\StatsAggregationsService;
use App\Services\StatsIncrementService;

class StatsObserver
{
    public static bool $enabled = true;

    public function __construct(
        protected StatsAggregationsService $statsAggregationsService,
        protected StatsIncrementService $statsIncrementService,
    ) {}

    public function updated(BaseStat $stat): void
    {
        if (! static::$enabled) {
            return;
        }

        $this->statsAggregationsService->invalidateByStatsUpdate($stat);
    }

    public function created(BaseStat $stat): void
    {
        if (! static::$enabled) {
            return;
        }

        $stat->team->increment('events_count_monthly');
        $stat->team->increment('events_count_overall');
        $stat->project->increment('events_count_monthly');

        // Alerts fire from the ingest jobs (TrackStatsUser / TrackStatsPayment), not here: a
        // StatsUser row can be created as an empty placeholder by a login/payment/event that beats
        // the registration ingest, which would send the alert with empty tracking data.

        $this->statsIncrementService->handleCreated($stat);
    }

    public function deleted(BaseStat $stat): void
    {
        if (! static::$enabled) {
            return;
        }

        // Mirror created(): the event counters are increment-only otherwise, so a row that was
        // counted and later removed (dedup/merge maintenance, import undo) would inflate usage
        // forever. The decrement is floored at 0 and scope-free so it always hits the right rows
        // regardless of the current team context.
        $this->decrementCounter(Team::class, $stat->team_id, 'events_count_monthly');
        $this->decrementCounter(Team::class, $stat->team_id, 'events_count_overall');
        $this->decrementCounter(Project::class, $stat->project_id, 'events_count_monthly');

        $this->statsAggregationsService->invalidateByStatsUpdate($stat);
    }

    private function decrementCounter(string $model, int $id, string $column): void
    {
        $model::withoutGlobalScopes()->whereKey($id)->where($column, '>', 0)->decrement($column);
    }

    public function restored(BaseStat $stat): void
    {
        if (! static::$enabled) {
            return;
        }

        $this->statsAggregationsService->invalidateByStatsUpdate($stat);
    }

    public function forceDeleted(BaseStat $stat): void
    {
        if (! static::$enabled) {
            return;
        }

        $this->statsAggregationsService->invalidateByStatsUpdate($stat);
    }
}
