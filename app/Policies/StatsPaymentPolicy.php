<?php

namespace App\Policies;

use App\Models\Instance;
use App\Models\StatsPayment;
use App\Services\SubscriptionService;
use Illuminate\Auth\Access\HandlesAuthorization;

class StatsPaymentPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can create models.
     */
    public function create(Instance $instance): bool
    {
        $allowedPayments = $instance->team->owner->sparkPlan()->options['payments'];

        if ($allowedPayments === SubscriptionService::UNLIMITED_AMOUNT)
            return true;

        return StatsPayment::count() < $allowedPayments;
    }
}
