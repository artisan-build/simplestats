<?php

namespace App\Observers;

use App\Models\BaseStat;
use App\Models\StatsPayment;
use App\Models\StatsUser;
use App\Models\UserNotification;
use App\Notifications\ProjectAlertPaymentNotification;
use App\Notifications\ProjectAlertRegistrationNotification;
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
