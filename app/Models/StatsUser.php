<?php

namespace App\Models;

use App\Observers\StatsObserver;
use App\Services\StatsService;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Stevebauman\Location\Facades\Location;
use hisorange\BrowserDetect\Facade as Browser;

#[ObservedBy(StatsObserver::class)]
class StatsUser extends BaseStat
{
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
        'track_source',
        'track_medium',
        'track_campaign',
        'track_term',
        'track_content',
        'time',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($statsUser) {
            foreach (StatsService::getStatsTypes() as $statsTypes) {
                foreach ($statsTypes as $item) {
                    $property = $item['type'];
                    if (!empty($statsUser->$property)) {
                        $trackSource = ['team_id' => $statsUser->team_id, 'project_id' => $statsUser->project_id, 'name' => $statsUser->$property];
                        $statsProperty = "stats_{$property}_id";
                        $item['class']::unguard();
                        $statsUser->$statsProperty = $item['class']::where($trackSource)->first()?->id ?? $item['class']::create($trackSource)->id;
                    }
                    unset($statsUser->$property);
                }
            }
        });
    }

    public function statsLogins(): HasMany
    {
        return $this->hasMany(StatsLogin::class);
    }

    public function statsPayments(): HasMany
    {
        return $this->hasMany(StatsPayment::class);
    }

    public function ip(): Attribute
    {
        return Attribute::make(
            // when the ip is set, we set the location data corresponding to it
            set: function(string $value) {
                if ($position = Location::get($value)) {
                    return [
                        'location_country' => $position->countryName,
                        'location_region' => $position->regionName,
                        'location_city' => $position->cityName,
                    ];
                }

                return [];
            },
        );
    }

    public function userAgent(): Attribute
    {
        return Attribute::make(
            // when the user agent is set, we set the device data corresponding to it
            set: function(string $value) {
                if ($browser = Browser::parse($value)) {
                    return [
                        'device_type' => $browser->deviceType(),
                        'device_platform' => $browser->platformFamily(),
                        'device_browser' => $browser->browserFamily(),
                    ];
                }

                return [];
            },
        );
    }
}
