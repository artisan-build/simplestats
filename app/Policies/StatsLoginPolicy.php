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
     * Determine whether the user can create models.
     */
    public function create(Instance $instance): bool
    {
        $allowedLogins = $instance->team->owner->sparkPlan()->options['logins'];

        if ($allowedLogins === SubscriptionService::UNLIMITED_AMOUNT)
            return true;

        return StatsLogin::count() < $allowedLogins;
    }
}
