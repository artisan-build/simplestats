<?php

namespace App\Policies;

use App\Models\Campaign;
use App\Models\User;
use App\Services\StatsService;
use Illuminate\Auth\Access\HandlesAuthorization;

class CampaignPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasTeamPermission($user->currentTeam, 'action:read')
            && $user->currentTeam->tracksPayments();
    }

    public function view(User $user, Campaign $campaign): bool
    {
        return $user->hasTeamPermission($campaign->team, 'action:read')
            && $campaign->project->hasTrackingUsageBits([StatsService::TRACKING_USAGE_BITS_PAYMENTS]);
    }

    public function create(User $user): bool
    {
        return $user->hasTeamPermission($user->currentTeam, 'action:create')
            && $user->currentTeam->tracksPayments();
    }

    public function update(User $user, Campaign $campaign): bool
    {
        return $user->hasTeamPermission($campaign->team, 'action:update');
    }
}
