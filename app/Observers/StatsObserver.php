<?php

namespace App\Observers;

use App\Models\BaseStat;
use App\Services\StatsAggregationsService;

class StatsObserver
{
    public function __construct(
        protected StatsAggregationsService $statsAggregationsService
    ) {
    }

    public function updated(BaseStat $stat): void
    {
        $this->statsAggregationsService->invalidateByStatsUpdate($stat);
    }

    public function created(BaseStat $stat): void
    {
        $stat->team->increment('events_count_monthly');
        $stat->team->increment('events_count_overall');

        $this->statsAggregationsService->invalidateByStatsUpdate($stat);
    }

    public function deleted(BaseStat $stat): void
    {
        $this->statsAggregationsService->invalidateByStatsUpdate($stat);
    }

    public function restored(BaseStat $stat): void
    {
        $this->statsAggregationsService->invalidateByStatsUpdate($stat);
    }

    public function forceDeleted(BaseStat $stat): void
    {
        $this->statsAggregationsService->invalidateByStatsUpdate($stat);
    }
}
