<?php

namespace App\Models;

use App\Concerns\HasStatsTracking;
use App\Observers\StatsObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Support\Facades\Hash;

#[ObservedBy(StatsObserver::class)]
class StatsVisitor extends BaseStat
{
    use HasStatsTracking;

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
    ];

    /**
     * Plausible Style:
     * @link https://plausible.io/data-policy#how-we-count-unique-users-without-cookies
     *
     * @param  string|null  $time
     * @param  string|null  $ip
     * @param  string|null  $userAgent
     * @return string
     */
    public static function createHash(?string $time, ?string $ip, ?string $userAgent): string
    {
        return substr(hash('sha256', $ip.$userAgent.$time.config('app.key')), 0, 32);
    }
}
