<?php

namespace App\Models;

use App\Concerns\HasCounterByStatsUser;
use App\Concerns\HasStatsUserAssurance;
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
    ];

    public function statsUser(): BelongsTo
    {
        return $this->belongsTo(StatsUser::class);
    }

    public function statsVisitor(): BelongsTo
    {
        return $this->belongsTo(StatsVisitor::class);
    }
}
