<?php

namespace App\Models;

use App\Concerns\HasCounterByStatsUser;
use App\Concerns\HasStatsUserAssurance;
use App\Enums\SubscriptionInterval;
use App\Notifications\ProjectAlertPaymentNotification;
use App\Observers\StatsObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[ObservedBy(StatsObserver::class)]
class StatsPayment extends BaseStat
{
    use HasCounterByStatsUser;
    use HasStatsUserAssurance;

    public $incrementing = false;

    protected $casts = [
        'time' => 'datetime',
        'gross' => 'float',
        'net' => 'float',
        'orig_gross' => 'float',
        'orig_net' => 'float',
        'subscription_interval' => SubscriptionInterval::class,
    ];

    public function statsUser(): BelongsTo
    {
        return $this->belongsTo(StatsUser::class);
    }

    public function statsVisitor(): BelongsTo
    {
        return $this->belongsTo(StatsVisitor::class);
    }

    public function sendPaymentAlerts(): void
    {
        $this->project->userAlertNotifications()->with('user')
            ->where('settings->tracking->payments', true)
            ->each(function (UserNotification $notification) {
                $notification->user->notify(new ProjectAlertPaymentNotification($this, $notification));
            });
    }
}
