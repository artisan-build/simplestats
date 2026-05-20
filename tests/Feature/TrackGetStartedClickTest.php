<?php

use App\Services\CustomEventTrackingService;
use SimpleStatsIo\LaravelClient\Facades\SimplestatsClient;
use SimpleStatsIo\LaravelClient\SimplestatsClient as SimplestatsClientClass;

it('tracks a custom event and returns success', function () {
    $this->partialMock(SimplestatsClientClass::class)
        ->shouldReceive('trackCustomEvent')
        ->once()
        ->withArgs(function (string $id, string $name, $user) {
            return str_starts_with($id, 'get-started-click-')
                && $name === 'Get Started Clicked'
                && is_string($user->getKey())
                && $user->getKey() !== '';
        });

    $response = $this->post(route('tracking.get-started-click'));

    $response->assertOk()
        ->assertJson(['tracked' => true]);
});

it('does not track when no visitor hash is available (e.g. CLI / queue context)', function () {
    SimplestatsClient::spy();

    app(CustomEventTrackingService::class)->trackGetStartedClick();

    SimplestatsClient::shouldNotHaveReceived('trackCustomEvent');
});
