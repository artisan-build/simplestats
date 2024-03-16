<?php

namespace App\Policies;

use App\Models\Instance;
use App\Models\StatsLogin;
use App\Services\SubscriptionService;
use Illuminate\Auth\Access\HandlesAuthorization;

class StatsLoginPolicy
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
        $allowedLogins = $authModel->team->owner->sparkPlan()->options[StatsLogin::class];

        if ($allowedLogins === SubscriptionService::UNLIMITED_AMOUNT)
            return true;

        return StatsLogin::withoutGlobalScope('instance')->count()+$delta < $allowedLogins;
    }
}
