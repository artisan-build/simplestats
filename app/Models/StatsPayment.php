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
        'gross',
        'net',
        'currency',
        'orig_gross',
        'orig_net',
        'orig_currency',
        'time',
    ];

    public function statsUser(): BelongsTo
    {
        return $this->belongsTo(StatsUser::class);
    }
}
