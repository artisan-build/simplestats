<?php

namespace App\Models;

use App\Concerns\HasCounterByStatsUser;
use App\Concerns\HasStatsUserAssurance;
use App\Observers\StatsObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[ObservedBy(StatsObserver::class)]
class StatsLogin extends BaseStat
{
    use HasCounterByStatsUser;
    use HasStatsUserAssurance;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'stats_user_id',
        'time'
    ];

    public function statsUser(): BelongsTo
    {
        return $this->belongsTo(StatsUser::class);
    }
}
