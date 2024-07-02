<?php

namespace App\Models;

use App\Observers\StatsObserver;
use App\Services\FaviconService;
use App\Services\RefererService;
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
        'track_referer',
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
            static::handleStatsTypes($statsUser);
        });

        static::updating(function ($statsUser) {
            static::handleStatsTypes($statsUser);
        });
    }

    private static function handleStatsTypes(StatsUser $statsUser): void
    {
        foreach (StatsService::getStatsTypes() as $statsTypes) {
            foreach ($statsTypes as $item) {
                $property = $item['type'];
                if (!empty($statsUser->$property)) {
                    $track = ['team_id' => $statsUser->team_id, 'project_id' => $statsUser->project_id, 'name' => $statsUser->$property];
                    $statsProperty = "stats_{$property}_id";
                    $item['model']::unguard();
                    $statsUser->$statsProperty = $item['model']::where($track)->first()?->id ?? $item['model']::create($track)->id;
                }
                unset($statsUser->$property);
            }
        }
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
                        'location_country' => "$position->countryName ($position->countryCode)",
                        'location_region' => "$position->regionName ($position->countryCode)",
                        'location_city' => "$position->cityName ($position->countryCode)",
                    ];
                }

                return [];
            },
        );
    }

    public function trackReferer(): Attribute
    {
        return Attribute::make(
            set: function(string $value) {
                return $this->setReferer($value);
            },
        );
    }

    public function trackSource(): Attribute
    {
        return Attribute::make(
            set: function(string $value) {
                // if source contains a dot, we assume it's a referrer.
                // compatibility where referers where sent via source
                if (str($value)->contains('.')) {
                    // this is not working: $this->track_referer = $value;
                    return $this->setReferer($value);
                } else {
                    return ['track_source' => $value];
                }
            },
        );
    }

    protected function setReferer(string $value): array
    {
        $value = app(RefererService::class)->getTopReferer($value);
        app(FaviconService::class)->download($value);
        return ['track_referer' => $value];
    }

    public function userAgent(): Attribute
    {
        return Attribute::make(
            // when the user agent is set, we set the device data corresponding to it
            set: function(string $value) {
                if ($browser = Browser::parse(urldecode($value))) {
                    return [
                        'device_type' => $browser->deviceType(),
                        'device_platform' => $browser->platformFamily(),
                        'device_browser' => match (true) {
                            $browser->isChrome() => 'Chrome',
                            $browser->isFirefox() => 'Firefox',
                            $browser->isOpera() => 'Opera',
                            $browser->isSafari() => 'Safari',
                            $browser->isEdge() => 'Microsoft Edge',
                            default => null,
                        },
                    ];
                }

                return [];
            },
        );
    }
}
