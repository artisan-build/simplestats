<?php

use App\Concerns\HasStatsTracking;
use App\Models\BaseStat;
use DeviceDetector\Parser\Client\Browser;

function makeStat(): BaseStat
{
    return new class extends BaseStat
    {
        use HasStatsTracking;

        protected $guarded = [];
    };
}

dataset('user_agents', [
    'chrome on mac' => [
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/129.0.0.0 Safari/537.36',
        ['device_type' => 'Desktop', 'device_platform' => 'Mac', 'device_browser' => 'Chrome'],
    ],
    'firefox on windows' => [
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:121.0) Gecko/20100101 Firefox/121.0',
        ['device_type' => 'Desktop', 'device_platform' => 'Windows', 'device_browser' => 'Firefox'],
    ],
    'chromium edge on windows' => [
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0 Safari/537.36 Edg/120.0.2210.121',
        ['device_type' => 'Desktop', 'device_platform' => 'Windows', 'device_browser' => 'Microsoft Edge'],
    ],
    'legacy edge on windows' => [
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/64.0 Safari/537.36 Edge/18.17763',
        ['device_type' => 'Desktop', 'device_platform' => 'Windows', 'device_browser' => 'Microsoft Edge'],
    ],
    'opera on windows' => [
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0 Safari/537.36 OPR/106.0.0.0',
        ['device_type' => 'Desktop', 'device_platform' => 'Windows', 'device_browser' => 'Opera'],
    ],
    'safari on mac' => [
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Safari/605.1.15',
        ['device_type' => 'Desktop', 'device_platform' => 'Mac', 'device_browser' => 'Safari'],
    ],
    'safari on iphone is mobile' => [
        'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1',
        ['device_type' => 'Mobile', 'device_platform' => 'iOS', 'device_browser' => 'Safari'],
    ],
    'safari on ipad is tablet with ipados' => [
        'Mozilla/5.0 (iPad; CPU OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1',
        ['device_type' => 'Tablet', 'device_platform' => 'iPadOS', 'device_browser' => 'Safari'],
    ],
    'chrome on android phone' => [
        'Mozilla/5.0 (Linux; Android 14; SM-S918B) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Mobile Safari/537.36',
        ['device_type' => 'Mobile', 'device_platform' => 'Android', 'device_browser' => 'Chrome'],
    ],
    'firefox on ubuntu' => [
        'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:121.0) Gecko/20100101 Firefox/121.0',
        ['device_type' => 'Desktop', 'device_platform' => 'Ubuntu', 'device_browser' => 'Firefox'],
    ],
    'chromium-based samsung browser buckets as chrome' => [
        'Mozilla/5.0 (Linux; Android 13; SAMSUNG SM-S918B) AppleWebKit/537.36 (KHTML, like Gecko) SamsungBrowser/22.0 Chrome/115.0.0.0 Mobile Safari/537.36',
        ['device_type' => 'Mobile', 'device_platform' => 'Android', 'device_browser' => 'Chrome'],
    ],
    'chromium-based brave buckets as chrome' => [
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 Chrome/120.0.0.0 Safari/537.36 Brave/120',
        ['device_type' => 'Desktop', 'device_platform' => 'Mac', 'device_browser' => 'Chrome'],
    ],
    'ie11 returns no recognised browser' => [
        'Mozilla/5.0 (Windows NT 10.0; WOW64; Trident/7.0; rv:11.0) like Gecko',
        ['device_type' => 'Desktop', 'device_platform' => 'Windows', 'device_browser' => null],
    ],
    'empty user agent returns no data' => [
        '',
        ['device_type' => null, 'device_platform' => null, 'device_browser' => null],
    ],
    'unrecognised user agent returns no data' => [
        'totally-random-string',
        ['device_type' => null, 'device_platform' => null, 'device_browser' => null],
    ],
]);

test('parses user agent into device data', function (string $userAgent, array $expected): void {
    $stat = makeStat();
    $stat->user_agent = $userAgent;

    expect($stat->device_type)->toBe($expected['device_type'])
        ->and($stat->device_platform)->toBe($expected['device_platform'])
        ->and($stat->device_browser)->toBe($expected['device_browser']);
})->with('user_agents');

test('matomo still exposes the browser family names we map to', function (): void {
    // The userAgent attribute relies on these five family strings being present
    // in matomo's $browserFamilies array. If matomo ever renames one, this test
    // fails so we know to update the mapping before stats_device_browsers gets duplicates.
    $families = Browser::getAvailableBrowserFamilies();

    expect(array_keys($families))->toContain('Chrome', 'Firefox', 'Opera', 'Safari');
});
