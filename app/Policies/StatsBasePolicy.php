<?php

namespace App\Policies;

use App\Services\SubscriptionService;
use Illuminate\Auth\Access\HandlesAuthorization;

class StatsBasePolicy
{
    use HandlesAuthorization;

    public function create($authModel, int $delta = 0): bool
    {
        $allowedEvents = $authModel->team->owner->sparkPlan()->options['Events'];

        if ($allowedEvents === SubscriptionService::UNLIMITED_AMOUNT) {
            return true;
        }

        return $authModel->team->events_count_monthly + $delta < $allowedEvents;
    }
}
