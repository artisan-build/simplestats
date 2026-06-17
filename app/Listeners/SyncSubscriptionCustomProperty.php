<?php

namespace App\Listeners;

use App\Analytics\UserCustomPropertiesResolver;
use Laravel\Paddle\Events\SubscriptionCanceled;
use Laravel\Paddle\Events\SubscriptionCreated;
use Laravel\Paddle\Events\SubscriptionUpdated;
use SimpleStatsIo\LaravelClient\Contracts\TrackablePerson;
use SimpleStatsIo\LaravelClient\Facades\SimplestatsClient;

class SyncSubscriptionCustomProperty
{
    public function __construct(private UserCustomPropertiesResolver $resolver) {}

    public function handle(SubscriptionCreated|SubscriptionUpdated|SubscriptionCanceled $event): void
    {
        $user = $event->subscription->billable;

        if (! $user instanceof TrackablePerson) {
            return;
        }

        SimplestatsClient::trackCustomProperties($this->resolver->resolve($user), $user);
    }
}
