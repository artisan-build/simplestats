<?php

namespace App\Http\DataProviders;

use App\DTOs\StatsFilterDto;
use App\Http\Requests\StatsFilterRequest;
use App\Services\RefererService;
use App\Services\StatsDetailsService;
use App\Services\StatsUserService;
use App\Services\SubscriptionService;

class PowerUsersDataProvider extends AbstractStatsDataProvider
{
    public function __construct(
        StatsFilterRequest $request,
        StatsDetailsService $statsDetailsService,
        SubscriptionService $subscriptionService,
        RefererService $refererService,
        private readonly StatsUserService $statsUserService,
    ) {
        parent::__construct($request, $statsDetailsService, $subscriptionService, $refererService);
    }

    public function toArray(): array
    {
        $user = $this->request->user();
        $effective = $this->resolveEffectiveRequest($user);

        $filters = StatsFilterDto::fromRequest($effective);

        $teamStats = $this->loadTeamStats($user, $filters);

        $selectedProject = $filters->projectId !== StatsFilterDto::TRACK_ALL
            ? $teamStats->projects->firstWhere('id', $filters->projectId)
            : null;

        // Mirror ProjectPolicy::update without touching $selectedProject->team, which is
        // not eager loaded here and would trip preventLazyLoading. $teamStats IS the
        // project's team, so the team-level permission check is equivalent.
        $canUpdateProject = $selectedProject !== null
            && $user->hasTeamPermission($teamStats, 'action:update');

        $sort = array_key_exists((string) $effective->query('sort'), StatsUserService::POWER_USER_SORTS)
            ? $effective->query('sort')
            : null;
        $direction = $effective->query('direction') === 'asc' ? 'asc' : 'desc';

        return array_merge($this->commonProps($user, $filters, $teamStats), [
            'powerUsers' => $this->statsUserService->powerUsers($filters, $sort, $direction),
            'sort' => [
                'column' => $sort,
                'direction' => $direction,
            ],
            'permissions' => [
                'canUpdateProject' => $canUpdateProject,
            ],
        ]);
    }
}
