<?php

namespace App\Http\DataProviders;

use App\DTOs\StatsFilterDto;
use App\Enums\StatsRangeType;
use App\Http\Requests\StatsFilterRequest;
use App\Models\Campaign;
use App\Models\Team;
use App\Services\AdSpendService;
use App\Services\RefererService;
use App\Services\StatsDetailsService;
use App\Services\StatsService;
use App\Services\SubscriptionService;

class StatsIndexDataProvider extends AbstractStatsDataProvider
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
        $teamStats = $this->buildStats($teamStats, $filters);

        return array_merge($this->commonProps($user, $filters, $teamStats), [
            'mrr_series' => $this->buildMrrTimeSeries($teamStats, $filters),
            'mrr_series_prev' => $this->buildMrrTimeSeriesComparison($teamStats, $filters),
            'campaign_summary' => $this->campaignSummary($filters),
        ]);
    }

    private function campaignSummary(StatsFilterDto $filters): ?array
    {
        if (empty($filters->campaign)) {
            return null;
        }

        // Spend has no per-dimension breakdown, so ROAS/CAC/net profit only hold for the
        // whole campaign: any other active filter shrinks revenue but not spend.
        if (array_keys($filters->toFilterArray()) !== ['campaign']) {
            return null;
        }

        $campaign = Campaign::find($filters->campaign);

        return $campaign === null ? null : $this->adSpendService->campaignSummary($filters, $campaign);
    }

    private function buildStats(Team $teamStats, StatsFilterDto $filters): Team
    {
        $teamStats->stats = $this->statsDetailsService->getStats($filters);

        if ($filters->comparison && $filters->rangeType !== StatsRangeType::YEAR) {
            $teamStats->statsPrev = array_values($this->statsDetailsService->getStats(
                StatsService::createPrevFiltersForComparison($filters),
            ));
        }

        return $teamStats;
    }
}
