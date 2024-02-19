<?php

namespace App\Policies;

use App\Models\Instance;
use App\Models\StatsUser;
use App\Services\SubscriptionService;
use Illuminate\Auth\Access\HandlesAuthorization;

class StatsUserPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can create models.
     */
    public function create(Instance $instance): bool
    {
        $allowedUsers = $instance->team->owner->sparkPlan()->options['users'];

        if ($allowedUsers === SubscriptionService::UNLIMITED_AMOUNT)
            return true;

        return StatsUser::count() < $allowedUsers;
    }
}
