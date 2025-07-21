<?php

namespace App\Policies;

use App\Models\Instance;
use App\Models\StatsVisitor;
use App\Services\SubscriptionService;
use Illuminate\Auth\Access\HandlesAuthorization;

class StatsVisitorPolicy extends StatsBasePolicy
{
}
