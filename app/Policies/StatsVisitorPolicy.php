<?php

namespace App\Policies;

use App\Models\Instance;
use App\Models\StatsVisitor;
use App\Services\SubscriptionService;
use Illuminate\Auth\Access\HandlesAuthorization;

class StatsVisitorPolicy
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
        $allowedVisitors = $authModel->team->owner->sparkPlan()->options[StatsVisitor::class];

        if ($allowedVisitors === SubscriptionService::UNLIMITED_AMOUNT)
            return true;

        return StatsVisitor::withoutGlobalScope('instance')->count()+$delta < $allowedVisitors;
    }
}
