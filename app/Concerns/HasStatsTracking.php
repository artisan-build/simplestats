<?php

namespace App\Concerns;

use App\Helpers\FileHelper;
use App\Models\BaseStat;
use App\Services\RefererService;
use App\Services\StatsService;
use hisorange\BrowserDetect\Facade as Browser;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Str;
use Stevebauman\Location\Facades\Location;

trait HasStatsTracking
{
    public static function bootHasStatsTracking(): void
    {
        static::creating(function (BaseStat $statsModel) {
            static::handleStatsTypes($statsModel);
        });

        static::saving(function (BaseStat $statsModel) {
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
                if (! empty($statsModel->$property)) {
                    $track = [
                        'team_id' => $statsModel->team_id,
                        // there is a composite index on the name col
                        // we can't make it longer then 255 charts (string indexes can't be so long), so we need to trim
                        'name' => Str::limit($statsModel->$property, 252),
                    ];

                    $statsProperty = "stats_{$property}_id";
                    $item['model']::unguard();
                    $statsModel->$statsProperty = $item['model']::firstOrCreate($track)->id;
                }
                unset($statsModel->$property);
            }
        }
    }

    public function ip(): Attribute
    {
        return Attribute::make(
            // if the ip is set, we set the location data corresponding to it
            set: function (?string $value) {
                if (is_null($value)) {
                    return [];
                }

                if ($value === 'localhost' || str($value)->startsWith('127.') || $value === '::1') {
                    return [
                        'location_country' => 'Localhost',
                        'location_region' => 'Cyberland',
                        'location_city' => 'Hackertwon',
                    ];
                }

                if (filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
                    return [
                        'location_country' => 'Intranet',
                        'location_region' => 'Network',
                        'location_city' => 'LAN',
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
            set: function (?string $value) {
                return $this->setReferer($value);
            },
        );
    }

    public function trackSource(): Attribute
    {
        return Attribute::make(
            set: function (?string $value) {
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
        if (! $value || filter_var($value, FILTER_VALIDATE_IP) !== false) {
            return ['track_referer' => null];
        }

        $value = Str::replace('www.', '', $value);

        $value = app(RefererService::class)->getTopReferer($value);

        return ['track_referer' => $value];
    }

    public function userAgent(): Attribute
    {
        return Attribute::make(
            // if the user agent is set, we set the device data corresponding to it
            set: function (?string $value) {
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

    public function pageEntry(): Attribute
    {
        return Attribute::make(
            set: function (?string $value) {
                if (is_null($value)) {
                    return null;
                }

                // if the page is only "/" we keep it else remove last "/" from page entry for consistency
                if ($value !== '/' && Str::endsWith($value, '/')) {
                    $value = Str::beforeLast($value, '/');
                }

                if (FileHelper::hasNonExecutableWebFileExtension($value)) {
                    return null;
                }

                return $value;
            },
        );
    }
}
