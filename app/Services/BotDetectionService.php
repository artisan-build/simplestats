<?php

namespace App\Services;

use App\Location\Position;
use DeviceDetector\DeviceDetector;

class BotDetectionService
{
    /**
     * Datacenter/hosting IPs (AWS, GCP, Hetzner, ...) are practically always
     * bots faking a real browser User-Agent. VPN/proxy exits also sit in
     * datacenters but carry real people, so they are deliberately kept.
     * Fails open: unknown flags (fallback drivers, failed lookups) never drop
     * a visitor.
     */
    public static function isDatacenterIp(?string $ip): bool
    {
        if (! config('location.datacenter_ip_filter')) {
            return false;
        }

        if ($ip === null || filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            return false;
        }

        $position = app(LocationService::class)->get($ip);

        if (! $position instanceof Position) {
            return false;
        }

        return $position->isHosting === true && $position->isProxy !== true;
    }

    public static function isBot(?string $userAgent): bool
    {
        if (! is_string($userAgent) || $userAgent === '') {
            return true;
        }

        $detector = new DeviceDetector($userAgent);
        $detector->discardBotInformation();
        $detector->parse();

        if ($detector->isBot()) {
            return true;
        }

        // Real visitors come from browsers or mobile apps. Treat HTTP libraries
        // (curl, python-requests, Go-http-client, ...), feed readers, media
        // players, and PIM clients (email apps) as bot-like, as they almost
        // always represent automated/scripted traffic in a web analytics context.
        return ! $detector->isBrowser() && ! $detector->isMobileApp();
    }
}
