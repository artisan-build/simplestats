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

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id',
        'ip',
        'user_agent',
        'location_country',
        'location_region',
        'location_city',
        'device_type',
        'device_platform',
        'device_browser',
        'track_referer',
        'track_source',
        'track_medium',
        'track_campaign',
        'track_term',
        'track_content',
        'page_entry',
        'time',
        'time_utc',
    ];

    public function statsLogins(): HasMany
    {
        return $this->hasMany(StatsLogin::class);
    }

    public function statsPayments(): HasMany
    {
        return $this->hasMany(StatsPayment::class);
    }
}
