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
    public function create(): bool
    {
        $allowedLogins = auth()->user()->team->owner->sparkPlan()->options['logins'];

        if ($allowedLogins === SubscriptionService::UNLIMITED_AMOUNT)
            return true;

        return StatsLogin::withoutGlobalScope('instance')->count() < $allowedLogins;
    }
}
