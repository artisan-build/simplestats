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
    public function create(): bool
    {
        $allowedPayments = auth()->user()->team->owner->sparkPlan()->options['payments'];

        if ($allowedPayments === SubscriptionService::UNLIMITED_AMOUNT)
            return true;

        return StatsPayment::withoutGlobalScope('instance')->count() < $allowedPayments;
    }
}
