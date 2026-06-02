<?php

namespace App\Concerns;

use App\Helpers\FileHelper;
use App\Models\BaseStat;
use App\Models\Project;
use App\Services\RefererService;
use App\Services\StatsService;
use DeviceDetector\DeviceDetector;
use DeviceDetector\Parser\Client\Browser;
use DeviceDetector\Parser\Device\AbstractDeviceParser;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\Context;
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
                // never record the project's own domain as a source (compatibility to
                // older client versions that don't strip it yet).
                if ($this->isOwnProjectDomain($value)) {
                    return ['track_source' => null];
                }

                // if source contains a dot, we assume it's a referer (older clients
                // sent referers via the source field).
                if (str($value)->contains('.')) {
                    return $this->setReferer($value);
                }

                return ['track_source' => $value];
            },
        );
    }

    protected function setReferer(?string $value): array
    {
        // falsy values und ips werden zu null referers (Direct/Unknown)
        if (! $value || filter_var($value, FILTER_VALIDATE_IP) !== false) {
            return ['track_referer' => null];
        }

        $value = app(RefererService::class)->getTopReferer($value);

        // never record the project's own domain as a referer either (e.g. an older
        // client that did not yet strip in-app navigation from the document referrer).
        if ($this->isOwnProjectDomain($value)) {
            return ['track_referer' => null];
        }

        return ['track_referer' => $value];
    }

    /**
     * Whether the given source/referer value points at the project's own domain.
     * Both sides are reduced to a bare host and compared exactly, so a subdomain
     * (asdf.my-app.com) stays a legitimate referer and is kept.
     */
    protected function isOwnProjectDomain(?string $value): bool
    {
        if ($host = $this->normalizeHost($value)) {
            // An empty own host can never equal the (non-empty) value host, so the
            // exact comparison also covers the "no project domain" case.
            return $host === $this->normalizeHost($this->resolveProjectDomain());
        }

        return false;
    }

    /**
     * Reduce a url/host/source value to its bare, lowercased host without a leading
     * "www.". Returns an empty string when no host can be derived.
     *
     * Kept in sync with the client-side CheckTracking::extractHost() (laravel-client)
     * and TrackingData::extractHost() (php-client); update all three together.
     */
    protected function normalizeHost(?string $value): string
    {
        if (empty($value)) {
            return '';
        }

        $host = parse_url($value, PHP_URL_HOST)
            ?? parse_url('https://'.$value, PHP_URL_HOST)
            ?? '';

        return (string) str($host)->lower()->replaceFirst('www.', '');
    }

    /**
     * Resolve the current project's configured domain. During HTTP ingest the
     * authenticated "user" is the Project itself, so we read its domain without a
     * query. Other contexts (seeders, ad-hoc creates) fall back to a lookup by the
     * stat's project_id.
     */
    protected function resolveProjectDomain(): ?string
    {
        $user = auth()->user();

        if ($user instanceof Project) {
            return $user->domain;
        }

        $projectId = $this->project_id ?? Context::get('project_id');

        if (! $projectId) {
            return null;
        }

        return Project::withoutGlobalScope('team')
            ->whereKey($projectId)
            ->value('domain');
    }

    public function userAgent(): Attribute
    {
        return Attribute::make(
            // if the user agent is set, we set the device data corresponding to it
            set: function (?string $value) {
                if (! is_string($value) || $value === '') {
                    return [];
                }

                $detector = new DeviceDetector(urldecode($value));
                $detector->discardBotInformation();
                $detector->parse();

                // matomo returns the magic string 'UNK' for unparseable OS/client names
                // instead of null, so normalise both to null up front.
                $osName = $detector->getOs('name');
                $osName = ($osName && $osName !== 'UNK') ? $osName : null;

                $clientName = $detector->getClient('name');
                $clientName = ($clientName && $clientName !== 'UNK') ? $clientName : null;

                // matomo groups Microsoft Edge inside the "Internet Explorer" family for
                // historical reasons, so we identify it by name. Everything else falls
                // back to the browser family lookup.
                $browserFamily = $clientName ? Browser::getBrowserFamily((string) $clientName) : null;

                return [
                    // Map matomo's granular device types to the three buckets previously
                    // produced by hisorange so the stats_device_types lookup table stays consistent.
                    'device_type' => match ($detector->getDevice()) {
                        AbstractDeviceParser::DEVICE_TYPE_DESKTOP => 'Desktop',
                        AbstractDeviceParser::DEVICE_TYPE_SMARTPHONE,
                        AbstractDeviceParser::DEVICE_TYPE_FEATURE_PHONE,
                        AbstractDeviceParser::DEVICE_TYPE_PHABLET => 'Mobile',
                        AbstractDeviceParser::DEVICE_TYPE_TABLET => 'Tablet',
                        default => null,
                    },
                    'device_platform' => $osName,
                    'device_browser' => match (true) {
                        $clientName === 'Microsoft Edge' => 'Microsoft Edge',
                        in_array($browserFamily, ['Chrome', 'Firefox', 'Opera', 'Safari'], true) => $browserFamily,
                        default => null,
                    },
                ];
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
