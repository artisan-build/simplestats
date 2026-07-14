<?php

namespace App\Location;

use Illuminate\Support\Fluent;
use Illuminate\Support\Str;
use Stevebauman\Location\Drivers\IpApi;
use Stevebauman\Location\Position as BasePosition;

class IpApiDriver extends IpApi
{
    /**
     * Unlike the stock IpApi/IpApiPro split, this driver upgrades itself to the
     * pro endpoint (HTTPS, no rate limit) when an IP_API_TOKEN is configured,
     * so free and paid setups share one driver.
     */
    public function url(string $ip): string
    {
        $url = parent::url($ip).',proxy,hosting';

        if ($token = config('location.ip_api.token')) {
            return Str::replaceFirst('http://ip-api.com/', 'https://pro.ip-api.com/', $url).'&key='.$token;
        }

        return $url;
    }

    protected function hydrate(BasePosition $position, Fluent $location): BasePosition
    {
        $position = parent::hydrate($position, $location);

        if ($position instanceof Position) {
            $position->isProxy = $location->proxy;
            $position->isHosting = $location->hosting;
        }

        return $position;
    }
}
