<?php

namespace App\Http\DataProviders;

use App\DTOs\StatsFilterDto;
use App\Http\Requests\StatsFilterRequest;
use App\Services\AdSpendService;
use App\Services\RefererService;
use App\Services\StatsDetailsService;
use App\Services\SubscriptionService;

class CampaignsDataProvider extends AbstractStatsDataProvider
{
    public function __construct(
        StatsFilterRequest $request,
        StatsDetailsService $statsDetailsService,
        SubscriptionService $subscriptionService,
        RefererService $refererService,
        private AdSpendService $adSpendService,
    ) {
        parent::__construct($request, $statsDetailsService, $subscriptionService, $refererService);
    }

    public function toArray(): array
    {
        $user = $this->request->user();
        $effective = $this->resolveEffectiveRequest($user);

        $filters = StatsFilterDto::fromRequest($effective);

        $teamStats = $this->loadTeamStats($user, $filters);

        return array_merge($this->commonProps($user, $filters, $teamStats), [
            'campaigns' => $this->adSpendService->getCampaignProfitability($filters),
            'adSpends' => $this->adSpendService->listForFilter($filters),
            'availableCampaigns' => $this->adSpendService->availableCampaigns(),
        ]);
    }
}
