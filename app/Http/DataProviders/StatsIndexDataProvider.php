<?php

namespace App\Http\DataProviders;

use App\Concerns\BuildsMrrProps;
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
    use BuildsMrrProps;

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

        $showRetention = $this->request->routeIs('retention');

        return array_merge($this->commonProps($user, $filters, $teamStats), [
            'mrr_series' => $this->buildMrrTimeSeries($teamStats, $filters),
            'mrr_series_prev' => $this->buildMrrTimeSeriesComparison($teamStats, $filters),
            'campaign_summary' => $this->campaignSummary($filters),
            'showRetention' => $showRetention,
            'retention_cohorts' => $showRetention ? $this->buildRetentionCohorts($teamStats, $filters) : null,
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

    /**
     * @return array{period_count: int, bucket_labels: array<int, string>, cohorts: array<int, array{cohort: string, idx: int, size: int, retention: array<int, float|null>}>}|null
     */
    private function buildRetentionCohorts(Team $teamStats, StatsFilterDto $filters): ?array
    {
        if (! $this->projectTracksUsers($teamStats, $filters)) {
            return null;
        }

        return $this->statsDetailsService->getRetentionCohorts($filters);
    }

    /**
     * Retention is login-based, so it is gated on the USERS bit (registrations
     * and logins). The "All projects" view passes when any project tracks users.
     */
    private function projectTracksUsers(Team $teamStats, StatsFilterDto $filters): bool
    {
        return $this->projectTracksBit($teamStats, $filters, StatsService::TRACKING_USAGE_BITS_USERS);
    }
}
