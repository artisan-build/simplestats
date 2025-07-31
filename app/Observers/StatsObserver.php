<?php

namespace App\Observers;

use App\Models\BaseStat;
use App\Models\StatsPayment;
use App\Models\User;
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

        if ($stat instanceof StatsPayment && $projectPaymentAlertUsers = $stat->project->userAlertNotifications()->with('user')->where('settings->tracking->payments', true)->get()) {
            $projectPaymentAlertUsers->each(function (User $user) {
                //$user->notify(new \App\Notifications\ProjectPaymentAlertNotification($stat, $user));
            });
        }

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
