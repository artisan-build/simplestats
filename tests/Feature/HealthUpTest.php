<?php

it('answers the container health probe without auth', function (): void {
    $this->get('/up')->assertOk()->assertSee('OK');
});

it('does not start a session for the health probe', function (): void {
    $this->get('/up')->assertCookieMissing(config('session.cookie'));
});

it('trusts the reverse proxy configured via TRUSTED_PROXIES', function (?string $proxies, string $expectedIp): void {
    config(['trustedproxy.proxies' => $proxies]);

    $this->get('/up', ['X-Forwarded-For' => '203.0.113.9']);

    expect(request()->ip())->toBe($expectedIp);
})->with([
    'trusted' => ['*', '203.0.113.9'],
    'not configured' => [null, '127.0.0.1'],
]);
