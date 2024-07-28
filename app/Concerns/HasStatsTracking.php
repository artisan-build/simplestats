<?php

namespace App\Concerns;

use App\Models\BaseStat;
use App\Services\FaviconService;
use App\Services\RefererService;
use App\Services\StatsService;
use hisorange\BrowserDetect\Facade as Browser;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Stevebauman\Location\Facades\Location;

trait HasStatsTracking
{
    public static function bootHasStatsTracking(): void
    {
        static::creating(function (BaseStat $statsModel) {
            static::handleStatsTypes($statsModel);
        });

        static::updating(function (BaseStat $statsModel) {
            static::handleStatsTypes($statsModel);
        });
    }

    private static function handleStatsTypes(BaseStat $statsModel): void
    {
        foreach (StatsService::getStatsTypes() as $statsTypes) {
            foreach ($statsTypes as $item) {
                $property = $item['type'];
                if (!empty($statsModel->$property)) {
                    $track = ['team_id' => $statsModel->team_id, 'project_id' => $statsModel->project_id, 'name' => $statsModel->$property];
                    $statsProperty = "stats_{$property}_id";
                    $item['model']::unguard();
                    $statsModel->$statsProperty = $item['model']::where($track)->first()?->id ?? $item['model']::create($track)->id;
                }
                unset($statsModel->$property);
            }
        }
    }

    public function ip(): Attribute
    {
        return Attribute::make(
            // if the ip is set, we set the location data corresponding to it
            set: function(string $value) {
                if ($value === 'localhost' || $value === '127.0.0.1' || $value === '::1') {
                    return [
                        'location_country' => "Localhost",
                        'location_region' => "Cyberland",
                        'location_city' => "Hackertwon",
                    ];
                }

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
            set: function(?string $value) {
                return $this->setReferer($value);
            },
        );
    }

    public function trackSource(): Attribute
    {
        return Attribute::make(
            set: function(?string $value) {
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

    protected function setReferer(?string $value): array
    {
        // falsy values und ips werden zu null referers (Direct/Unknown)
        if (!$value || filter_var($value, FILTER_VALIDATE_IP) !== false) {
            return ['track_referer' => null];
        }

        $value = app(RefererService::class)->getTopReferer($value);
        app(FaviconService::class)->download($value);
        return ['track_referer' => $value];
    }

    public function userAgent(): Attribute
    {
        return Attribute::make(
            // if the user agent is set, we set the device data corresponding to it
            set: function(?string $value) {
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
                        }
                    ];
                }

                return [];
            },
        );
    }
}
