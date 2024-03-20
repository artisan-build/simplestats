<?php

namespace App\Policies;

use App\Models\StatsUser;
use App\Services\SubscriptionService;
use Illuminate\Auth\Access\HandlesAuthorization;

class StatsUserPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the auth model can create models.
     * Auth Model can be instance (rest api with personal instance access token) or auth user
     *
     * @param $authModel
     * @param  int  $delta
     * @return bool
     */
    public function create($authModel, int $delta = 0): bool
    {
        $allowedUsers = $authModel->team->owner->sparkPlan()->options[StatsUser::class];

        if ($allowedUsers === SubscriptionService::UNLIMITED_AMOUNT)
            return true;

        return StatsUser::withoutGlobalScope('instance')->count()+$delta < $allowedUsers;
    }
}
