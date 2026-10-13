<?php

use App\Services\CustomEventTrackingService;
use SimpleStatsIo\LaravelClient\Facades\SimplestatsClient;
use SimpleStatsIo\LaravelClient\SimplestatsClient as SimplestatsClientClass;

beforeEach(fn () => $this->skipUnlessSaas());

it('tracks a custom event and returns success', function (): void {
    $this->partialMock(SimplestatsClientClass::class)
        ->shouldReceive('trackCustomEvent')
        ->once()
        ->withArgs(fn (string $id, string $name, $user): bool => str_starts_with($id, 'get-started-click-')
            && $name === 'Get Started Clicked'
            && is_string($user->getKey())
            && $user->getKey() !== '');

    $response = $this->post(route('tracking.get-started-click'));

    $response->assertOk()
        ->assertJson(['tracked' => true]);
});

it('does not track when no visitor hash is available (e.g. CLI / queue context)', function (): void {
    SimplestatsClient::spy();

    resolve(CustomEventTrackingService::class)->trackGetStartedClick();

    SimplestatsClient::shouldNotHaveReceived('trackCustomEvent');
});
