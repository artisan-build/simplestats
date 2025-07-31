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

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id',
        'stats_user_id',
        'stats_user_time', // used for HasStatsUserAssurance
        'stats_visitor_id',
        'gross',
        'net',
        'currency',
        'orig_gross',
        'orig_net',
        'orig_currency',
        'time',
        'time_utc',
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
