<?php

use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Http\Request;

function shouldEnableSsr(string $uri): bool
{
    $middleware = new HandleInertiaRequests;
    $request = Request::create($uri);

    $method = new ReflectionMethod($middleware, 'shouldEnableSsr');

    return $method->invoke($middleware, $request);
}

it('enables SSR for the home page', function () {
    expect(shouldEnableSsr('/'))->toBeTrue();
});

it('enables SSR for public pages', function (string $path) {
    expect(shouldEnableSsr($path))->toBeTrue();
})->with([
    '/login',
    '/register',
    '/forgot-password',
    '/reset-password/some-token',
    '/imprint',
]);

it('enables SSR for localized public pages', function (string $path) {
    expect(shouldEnableSsr($path))->toBeTrue();
})->with([
    '/de',
    '/de/login',
    '/de/register',
    '/de/forgot-password',
    '/de/imprint',
    '/fr',
    '/fr/login',
    '/es',
]);

it('disables SSR for dashboard and other app pages', function (string $path) {
    expect(shouldEnableSsr($path))->toBeFalse();
})->with([
    '/dashboard',
    '/settings/profile',
    '/teams/1/projects',
    '/setup',
    '/blog',
    '/admin',
]);
