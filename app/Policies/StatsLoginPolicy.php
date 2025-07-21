<?php

namespace App\Policies;

use App\Models\Instance;
use App\Models\StatsLogin;
use App\Services\SubscriptionService;
use Illuminate\Auth\Access\HandlesAuthorization;

class StatsLoginPolicy extends StatsBasePolicy
{
}
