<?php

namespace App\Models;

use App\Concerns\HasStatsRelations;
use App\Concerns\HasStatsTracking;
use App\Observers\StatsObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[ObservedBy(StatsObserver::class)]
class StatsUser extends BaseStat
{
    use HasStatsTracking;
    use HasStatsRelations;

    public $incrementing = false;

    public function statsLogins(): HasMany
    {
        return $this->hasMany(StatsLogin::class);
    }

    public function statsPayments(): HasMany
    {
        return $this->hasMany(StatsPayment::class);
    }
}
