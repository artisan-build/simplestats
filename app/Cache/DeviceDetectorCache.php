<?php

namespace App\Cache;

use DeviceDetector\Cache\CacheInterface;
use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * Persists device-detector's parsed regex definitions in the app's cache so the
 * ~1.7 MB of YAML is parsed once per worker generation instead of on every
 * ingest request (the app runs on PHP-FPM, which resets device-detector's
 * in-process StaticCache between requests).
 *
 * Fail-open: the bot check runs synchronously before the ingest response, so a
 * failing cache store must never bubble up and 500 the endpoint. On error the
 * detector falls back to the uncached parse path: slower, but correct.
 */
class DeviceDetectorCache implements CacheInterface
{
    public function fetch(string $id)
    {
        try {
            return Cache::get($id);
        } catch (Throwable) {
            return null;
        }
    }

    public function contains(string $id): bool
    {
        try {
            return Cache::has($id);
        } catch (Throwable) {
            return false;
        }
    }

    public function save(string $id, $data, int $lifeTime = 0): bool
    {
        try {
            return Cache::put($id, $data, $lifeTime > 0 ? $lifeTime : null);
        } catch (Throwable) {
            return false;
        }
    }

    public function delete(string $id): bool
    {
        try {
            return Cache::forget($id);
        } catch (Throwable) {
            return false;
        }
    }

    public function flushAll(): bool
    {
        try {
            return Cache::flush();
        } catch (Throwable) {
            return false;
        }
    }
}
