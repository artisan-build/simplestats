<?php

namespace App\Services;

use App\Location\Position;
use Illuminate\Support\Facades\Cache;
use Stevebauman\Location\Facades\Location;
use Stevebauman\Location\Position as BasePosition;

class LocationService
{
    /**
     * In-process memo so the datacenter check and the geo mutator resolve
     * the same IP with a single lookup within one job.
     *
     * @var array<string, BasePosition|false>
     */
    protected array $memo = [];

    public function get(string $ip): BasePosition|false
    {
        if (count($this->memo) > 100) {
            $this->memo = [];
        }

        if (! array_key_exists($ip, $this->memo)) {
            $this->memo[$ip] = $this->resolve($ip);
        }

        return $this->memo[$ip];
    }

    /**
     * Successful lookups are cached for a day to stay well below ip-api's
     * free-tier rate limit (45 req/min). The cache key is a hashed IP that
     * rotates daily (same idea as the visitor hash salt) and the cached value
     * holds no IP, so no plain IP is ever persisted. Failed lookups are not
     * cached, a later job may succeed.
     */
    protected function resolve(string $ip): BasePosition|false
    {
        $key = 'location:'.hash('sha256', $ip.now('UTC')->toDateString());

        if (is_array($cached = Cache::get($key))) {
            return $this->hydrate($cached);
        }

        $position = Location::get($ip);

        if ($position instanceof BasePosition) {
            Cache::put($key, $this->dehydrate($position), now()->addDay());
        }

        return $position;
    }

    /**
     * @return array{countryName: ?string, countryCode: ?string, regionName: ?string, cityName: ?string, isHosting: ?bool, isProxy: ?bool}
     */
    protected function dehydrate(BasePosition $position): array
    {
        return [
            'countryName' => $position->countryName,
            'countryCode' => $position->countryCode,
            'regionName' => $position->regionName,
            'cityName' => $position->cityName,
            'isHosting' => $position instanceof Position ? $position->isHosting : null,
            'isProxy' => $position instanceof Position ? $position->isProxy : null,
        ];
    }

    protected function hydrate(array $cached): Position
    {
        $position = new Position;
        $position->countryName = $cached['countryName'];
        $position->countryCode = $cached['countryCode'];
        $position->regionName = $cached['regionName'];
        $position->cityName = $cached['cityName'];
        $position->isHosting = $cached['isHosting'];
        $position->isProxy = $cached['isProxy'];

        return $position;
    }
}
