<?php

use App\Analytics\UserCustomPropertiesResolver;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use SimpleStatsIo\LaravelClient\Contracts\TrackablePerson;

function trackablePersonWithPlan(string $planName, bool $hasSubscriptions = false): TrackablePerson
{
    return new class($planName, $hasSubscriptions) implements TrackablePerson
    {
        public function __construct(private string $planName, private bool $hasSubscriptions) {}

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
            return (object) ['name' => $this->planName];
        }

        public function subscriptions(): object
        {
            return new class($this->hasSubscriptions)
            {
                public function __construct(private bool $exists) {}

                public function exists(): bool
                {
                    return $this->exists;
                }
            };
        }
    };
}

it('returns the current plan as the subscription property in the saas build', function () {
    config(['app.app_type' => 'saas']);

    $properties = (new UserCustomPropertiesResolver)->resolve(trackablePersonWithPlan('Plus'));

    expect($properties)->toBe(['subscription' => 'Plus']);
});

it('reports a former subscriber back on the free plan as churned', function () {
    config(['app.app_type' => 'saas']);

    $properties = (new UserCustomPropertiesResolver)->resolve(trackablePersonWithPlan('Free', hasSubscriptions: true));

    expect($properties)->toBe(['subscription' => 'Churned']);
});

it('reports a free user that never subscribed as free', function () {
    config(['app.app_type' => 'saas']);

    $properties = (new UserCustomPropertiesResolver)->resolve(trackablePersonWithPlan('Free', hasSubscriptions: false));

    expect($properties)->toBe(['subscription' => 'Free']);
});

it('resolves nothing outside the saas build', function () {
    config(['app.app_type' => 'self-hosted']);

    $properties = (new UserCustomPropertiesResolver)->resolve(trackablePersonWithPlan('Plus'));

    expect($properties)->toBe([]);
});
