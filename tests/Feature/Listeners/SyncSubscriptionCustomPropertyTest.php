<?php

use App\Analytics\UserCustomPropertiesResolver;
use App\Listeners\SyncSubscriptionCustomProperty;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Laravel\Paddle\Events\SubscriptionUpdated;
use SimpleStatsIo\LaravelClient\Contracts\TrackablePerson;
use SimpleStatsIo\LaravelClient\Facades\SimplestatsClient;

function subscriptionUpdatedEventFor(mixed $billable): SubscriptionUpdated
{
    $event = Mockery::mock(SubscriptionUpdated::class);
    $event->subscription = (object) ['billable' => $billable];

    return $event;
}

it('re-tracks the current plan when a subscription changes', function () {
    config(['app.app_type' => 'saas']);

    $user = new class implements TrackablePerson
    {
        public function getKey()
        {
            return 1;
        }

        public function getTrackingTime(): CarbonInterface
        {
            return CarbonImmutable::now();
        }

        public function sparkPlan(): object
        {
            return (object) ['name' => 'Business'];
        }
    };

    SimplestatsClient::shouldReceive('trackCustomProperties')
        ->once()
        ->with(['subscription' => 'Business'], $user);

    (new SyncSubscriptionCustomProperty(new UserCustomPropertiesResolver))
        ->handle(subscriptionUpdatedEventFor($user));
});

it('ignores subscriptions whose billable is not trackable', function () {
    SimplestatsClient::shouldReceive('trackCustomProperties')->never();

    (new SyncSubscriptionCustomProperty(new UserCustomPropertiesResolver))
        ->handle(subscriptionUpdatedEventFor(new stdClass));
});
