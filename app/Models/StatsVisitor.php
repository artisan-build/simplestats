<?php

namespace App\Models;

use App\Concerns\HasStatsRelations;
use App\Concerns\HasStatsTracking;
use App\Observers\StatsObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;

#[ObservedBy(StatsObserver::class)]
class StatsVisitor extends BaseStat
{
    use HasStatsTracking;
    use HasStatsRelations;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'ip',
        'user_agent',
        'visitor_hash',
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
}
