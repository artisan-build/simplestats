<?php

namespace App\Services;

use App\Cache\DeviceDetectorCache;
use App\Location\Position;
use DeviceDetector\DeviceDetector;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class BotDetectionService
{
    /**
     * Autonomous systems of the datacenters that consumer VPN providers
     * (NordVPN, ExpressVPN, ...) run their exit nodes on. Traffic from these
     * carries real people, so it is kept even though the IP is a datacenter
     * IP. M247 and Datacamp/CDN77 host the vast majority of commercial VPN
     * exits; every other datacenter AS (AWS, GCP, Hetzner, ...) is filtered.
     */
    private const VPN_HOSTER_ASNS = [
        9009,   // M247
        60068,  // Datacamp / CDN77
        212238, // Datacamp (NordVPN exits)
    ];

    /**
     * Datacenter/hosting IPs (AWS, GCP, Hetzner, ...) are practically always
     * bots faking a real browser User-Agent, so they are dropped. The only
     * exception are the datacenters that host consumer VPN exits, which carry
     * real people (same approach as Plausible/Fathom). Fails open: unknown
     * hosting flags (fallback drivers, throttled lookups, failed lookups)
     * never drop a visitor.
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

        if (! $position instanceof Position || $position->isHosting === null) {
            if ($position instanceof Position) {
                self::recordFilterOutcome('null_flags_passed');
            }

            return false;
        }

        if ($position->isHosting !== true) {
            return false;
        }

        if (in_array($position->asn, self::VPN_HOSTER_ASNS, true)) {
            self::recordFilterOutcome('vpn_passed');

            return false;
        }

        self::recordFilterOutcome('dropped');

        return true;
    }

    /**
     * Daily counters (no IPs stored) so the filter yield can be measured
     * instead of estimated. `dropped` is the real yield, `null_flags_passed`
     * exposes how often ip-api throttling forces a fail-open (justifies the
     * pro key), `vpn_passed` is the VPN-exception blast radius.
     */
    protected static function recordFilterOutcome(string $outcome): void
    {
        $key = 'datacenter_filter:'.$outcome.':'.now('UTC')->toDateString();

        Cache::add($key, 0, now()->addDays(30));
        Cache::increment($key);
    }

    public static function isBot(?string $userAgent): bool
    {
        if (! is_string($userAgent) || $userAgent === '') {
            return true;
        }

        $detector = new DeviceDetector($userAgent);
        $detector->discardBotInformation();
        $detector->setCache(new DeviceDetectorCache);
        $detector->parse();

        if ($detector->isBot()) {
            return true;
        }

        // device-detector treats headless browsers as regular browsers, but they
        // are practically always automation, so match the User-Agent token
        // directly. Mirrors the client package's CheckTracking::isBot() so the
        // filter also protects customers who have not updated the package yet.
        if (Str::contains($userAgent, 'headless', ignoreCase: true)) {
            return true;
        }

        // Real visitors come from browsers or mobile apps. Treat HTTP libraries
        // (curl, python-requests, Go-http-client, ...), feed readers, media
        // players, and PIM clients (email apps) as bot-like, as they almost
        // always represent automated/scripted traffic in a web analytics context.
        return ! $detector->isBrowser() && ! $detector->isMobileApp();
    }
}
