<?php

use SimpleStatsIo\LaravelClient\Facades\SimplestatsClient;

it('tracks a custom event and returns success', function () {
    SimplestatsClient::spy();

    $response = $this
        ->withSession(['simplestats.visitor_hash' => 'abc123'])
        ->post(route('tracking.get-started-click'));

    $response->assertOk()
        ->assertJson(['tracked' => true]);

    SimplestatsClient::shouldHaveReceived('trackCustomEvent')
        ->once()
        ->withArgs(function (string $id, string $name, ?float $value, $user) {
            return str_starts_with($id, 'get-started-click-')
                && $name === 'Get Started Clicked'
                && $value === null
                && $user->getKey() === 'abc123';
        });
});

it('returns success without tracking when no visitor hash exists', function () {
    SimplestatsClient::spy();

    $response = $this->post(route('tracking.get-started-click'));

    $response->assertOk()
        ->assertJson(['tracked' => true]);

    SimplestatsClient::shouldNotHaveReceived('trackCustomEvent');
});
