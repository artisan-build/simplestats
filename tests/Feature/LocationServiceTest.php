<?php

use App\Location\Position;
use App\Services\LocationService;
use Stevebauman\Location\Facades\Location;
use Stevebauman\Location\LocationFake;
use Stevebauman\Location\LocationManager;

// Location::fake() cannot be called twice (it would wrap the previous fake),
// so subsequent fakes are swapped in manually.
function swapLocationFake(array $responses): void
{
    Location::swap(new LocationFake(new LocationManager, $responses));
}

test('successful location lookups are served from cache afterwards', function () {
    $position = new Position;
    $position->countryName = 'Germany';
    $position->countryCode = 'DE';
    $position->isHosting = true;
    $position->isProxy = false;
    Location::fake(['203.0.113.9' => $position]);

    expect(app(LocationService::class)->get('203.0.113.9')->isHosting)->toBeTrue();

    // A fresh service instance (empty memo) with a now-empty fake can only
    // succeed via the cache.
    swapLocationFake([]);
    $cached = (new LocationService)->get('203.0.113.9');

    expect($cached)->toBeInstanceOf(Position::class)
        ->and($cached->isHosting)->toBeTrue()
        ->and($cached->isProxy)->toBeFalse()
        ->and($cached->countryName)->toBe('Germany');
});

test('failed location lookups are not cached', function () {
    Location::fake([]);

    expect(app(LocationService::class)->get('203.0.113.10'))->toBeFalse();

    $position = new Position;
    $position->isHosting = true;
    swapLocationFake(['203.0.113.10' => $position]);

    expect((new LocationService)->get('203.0.113.10')->isHosting)->toBeTrue();
});
