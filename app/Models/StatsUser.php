<?php

namespace App\Models;

use App\Concerns\HasCustomProperties;
use App\Concerns\HasStatsRelations;
use App\Concerns\HasStatsTracking;
use App\Contracts\Propertyable;
use App\Notifications\ProjectAlertRegistrationNotification;
use App\Observers\StatsObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[ObservedBy(StatsObserver::class)]
class StatsUser extends BaseStat implements Propertyable
{
    use HasCustomProperties;
    use HasStatsRelations;
    use HasStatsTracking;

    public $incrementing = false;

    /**
     * @return HasMany<StatsLogin, $this>
     */
    public function statsLogins(): HasMany
    {
        return $this->hasMany(StatsLogin::class);
    }

    /**
     * @return HasMany<StatsPayment, $this>
     */
    public function statsPayments(): HasMany
    {
        return $this->hasMany(StatsPayment::class);
    }

    public function sendRegistrationAlerts(): void
    {
        $this->project->userAlertNotifications()->with('user')
            ->where('settings->tracking->registrations', true)
            ->each(function (UserNotification $notification): void {
                $notification->user->notify(new ProjectAlertRegistrationNotification($this, $notification));
            });
    }
}
