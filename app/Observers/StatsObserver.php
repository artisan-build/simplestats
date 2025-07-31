<?php

namespace App\Observers;

use App\Models\BaseStat;
use App\Models\StatsPayment;
use App\Models\StatsUser;
use App\Models\UserNotification;
use App\Notifications\ProjectAlertPaymentNotification;
use App\Notifications\ProjectAlertRegistrationNotification;
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

        if ($stat instanceof StatsUser) {
            $stat->project->userAlertNotifications()->with('user')
                ->where('settings->tracking->registrations', true)
                ->each(function (UserNotification $notification) use ($stat) {
                    $notification->user->notify(new ProjectAlertRegistrationNotification($stat, $notification));
                });
        }

        if ($stat instanceof StatsPayment) {
            $stat->project->userAlertNotifications()->with('user')
                ->where('settings->tracking->payments', true)
                ->each(function (UserNotification $notification) use ($stat) {
                    $notification->user->notify(new ProjectAlertPaymentNotification($stat, $notification));
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
