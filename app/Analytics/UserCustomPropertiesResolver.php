<?php

namespace App\Analytics;

use App\Services\SubscriptionService;
use SimpleStatsIo\LaravelClient\Contracts\ResolvesUserCustomProperties;
use SimpleStatsIo\LaravelClient\Contracts\TrackablePerson;

class UserCustomPropertiesResolver implements ResolvesUserCustomProperties
{
    public const VALUE_CHURNED = 'Churned';

    /**
     * @return array<string, scalar|null>
     */
    public function resolve(TrackablePerson $user): array
    {
        // sparkPlan()/subscriptions() rely on Spark/Paddle, absent in the self-hosted build.
        if (config('app.app_type') !== 'saas') {
            return [];
        }

        $plan = $user->sparkPlan()->name;

        // A former subscriber back on Free counts as churned, not as an organic Free user.
        if ($plan === SubscriptionService::PLAN_FREE && $user->subscriptions()->exists()) {
            return ['subscription' => self::VALUE_CHURNED];
        }

        return ['subscription' => $plan];
    }
}
