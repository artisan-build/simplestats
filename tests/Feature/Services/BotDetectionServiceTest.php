<?php

use App\Services\BotDetectionService;
use DeviceDetector\DeviceDetector;
use Illuminate\Contracts\Cache\Store;
use Illuminate\Support\Facades\Cache;

function deviceDetectorBotCacheKey(): string
{
    return preg_replace('/([^a-z0-9_-]+)/i', '', 'DeviceDetector-'.DeviceDetector::VERSION.'regexes-bot');
}

$realBrowser = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.4 Safari/605.1.15';

it('caches the parsed device-detection regexes across calls', function () use ($realBrowser) {
    config(['cache.default' => 'array']);
    Cache::store('array')->flush();

    expect(Cache::has(deviceDetectorBotCacheKey()))->toBeFalse();

    BotDetectionService::isBot($realBrowser);

    expect(Cache::has(deviceDetectorBotCacheKey()))->toBeTrue();
});

it('still detects bots correctly when the cache store fails', function () use ($realBrowser) {
    Cache::extend('throwing', fn () => Cache::repository(new class implements Store
    {
        public function get($key)
        {
            throw new RuntimeException('cache down');
        }

        public function many(array $keys)
        {
            throw new RuntimeException('cache down');
        }

        public function put($key, $value, $seconds)
        {
            throw new RuntimeException('cache down');
        }

        public function putMany(array $values, $seconds)
        {
            throw new RuntimeException('cache down');
        }

        public function increment($key, $value = 1)
        {
            throw new RuntimeException('cache down');
        }

        public function decrement($key, $value = 1)
        {
            throw new RuntimeException('cache down');
        }

        public function forever($key, $value)
        {
            throw new RuntimeException('cache down');
        }

        public function forget($key)
        {
            throw new RuntimeException('cache down');
        }

        public function flush()
        {
            throw new RuntimeException('cache down');
        }

        public function getPrefix()
        {
            return '';
        }
    }));

    config(['cache.stores.throwing' => ['driver' => 'throwing'], 'cache.default' => 'throwing']);

    expect(BotDetectionService::isBot('Googlebot/2.1 (+http://www.google.com/bot.html)'))->toBeTrue();
    expect(BotDetectionService::isBot($realBrowser))->toBeFalse();
});
