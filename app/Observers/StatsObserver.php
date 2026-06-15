<?php

namespace App\Observers;

use App\Models\BaseStat;
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

        $this->statsAggregationsService->invalidateByStatsUpdate($stat);
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
