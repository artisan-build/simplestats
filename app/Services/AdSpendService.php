<?php

namespace App\Services;

use App\DTOs\StatsFilterDto;
use App\Enums\StatsRangeType;
use App\Models\AdSpend;
use App\Models\Campaign;
use App\Models\Project;
use App\Models\StatsTrackCampaign;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class AdSpendService
{
    private const CAMPAIGN_LIMIT = 100;

    /**
     * Trailing window over which the subscription economics (ARPS, churn -> LTV)
     * behind LTV:CAC and CAC-payback are measured, independent of the UI range.
     * Kept short enough that the window stays close to actual recurring revenue:
     * the churn rate divides by the window's starting MRR, so a window reaching
     * back before a project's first subscriptions would yield a null lifetime.
     */
    private const ECONOMICS_TRAILING_MONTHS = 3;

    public function __construct(
        private StatsDetailsService $statsDetailsService,
    ) {}

    /**
     * Per-campaign profitability for the selected range. Each user-managed campaign
     * bundles one or more tracked utm_campaign dimensions; its revenue is the sum of
     * those dimensions' range-scoped revenue, its spend the overlap-allocated budget.
     * All campaigns are listed (even at zero), so a freshly created one is visible.
     * Paginated so the per-row LTV computation only runs for the visible page.
     *
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    public function getCampaignProfitability(StatsFilterDto $filters, int $perPage = 20): LengthAwarePaginator
    {
        [$windowStart, $windowEnd] = $this->resolveWindow($filters);

        $revenueByUtm = $this->revenueByUtm($filters);

        return $this->campaignsForFilter($filters)
            ->paginate($perPage)
            ->through(fn (Campaign $campaign) => $this->buildRow($campaign, $revenueByUtm, $windowStart, $windowEnd, $filters));
    }

    /**
     * @return array<string, mixed>
     */
    public function campaignSummary(StatsFilterDto $filters, Campaign $campaign): array
    {
        [$windowStart, $windowEnd] = $this->resolveWindow($filters);

        $campaign->loadMissing(['statsTrackCampaigns:id,campaign_id,name', 'adSpends', 'project:id,name,usage']);

        return $this->buildRow($campaign, $this->revenueByUtm($filters), $windowStart, $windowEnd, $filters);
    }

    /**
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    public function paginatedForCampaign(Campaign $campaign, int $perPage = 10): LengthAwarePaginator
    {
        return AdSpend::query()
            ->where('campaign_id', $campaign->id)
            ->orderByDesc('starts_on')
            ->paginate($perPage)
            ->through(fn (AdSpend $spend) => [
                'id' => $spend->id,
                'amount' => $spend->amount,
                'currency' => $spend->currency,
                'starts_on' => $spend->starts_on->toDateString(),
                'ends_on' => $spend->ends_on->toDateString(),
            ]);
    }

    /**
     * @return array<int, array{id: int, name: string}>
     */
    public function linkedTrackCampaigns(Campaign $campaign): array
    {
        return StatsTrackCampaign::query()
            ->where('campaign_id', $campaign->id)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->toArray();
    }

    /**
     * @return array<int, array{id: int, name: string}>
     */
    public function linkableTrackCampaigns(): array
    {
        return StatsTrackCampaign::query()
            ->whereNull('campaign_id')
            ->orderBy('name')
            ->get(['id', 'name'])
            ->toArray();
    }

    /**
     * @return Builder<Campaign>
     */
    private function campaignsForFilter(StatsFilterDto $filters): Builder
    {
        return Campaign::query()
            ->with(['statsTrackCampaigns:id,campaign_id,name', 'adSpends', 'project:id,name,usage'])
            ->when(
                $filters->projectId !== StatsFilterDto::TRACK_ALL,
                fn ($query) => $query->where('project_id', $filters->projectId),
            )
            ->orderByDesc('created_at');
    }

    /**
     * Range-scoped revenue per tracked utm_campaign dimension, reused from the
     * grouped track_campaign aggregation (already project- and range-filtered).
     *
     * @return Collection<int, array<string, mixed>> utm id => {net, reg, visitors}
     */
    private function revenueByUtm(StatsFilterDto $filters): Collection
    {
        return collect($this->statsDetailsService->getGroupedData($filters, 'track_campaign', self::CAMPAIGN_LIMIT, 'net'))
            ->keyBy(fn (array $row) => (int) ($row['type_id'] ?? 0));
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $revenueByUtm
     * @return array<string, mixed>
     */
    private function buildRow(Campaign $campaign, Collection $revenueByUtm, CarbonImmutable $windowStart, CarbonImmutable $windowEnd, StatsFilterDto $filters): array
    {
        $net = 0;
        $reg = 0;
        $visitors = 0;

        foreach ($campaign->statsTrackCampaigns as $utm) {
            $revenue = $revenueByUtm->get($utm->id);

            if ($revenue === null) {
                continue;
            }

            $net += (int) round($revenue['net'] ?? 0);
            $reg += (int) ($revenue['reg'] ?? 0);
            $visitors += (int) ($revenue['visitors'] ?? 0);
        }

        $spend = (int) $campaign->adSpends->sum(fn (AdSpend $adSpend) => $adSpend->allocatedFor($windowStart, $windowEnd));
        $cac = $reg > 0 ? (int) round($spend / $reg) : null;

        return [
            'campaign_id' => $campaign->id,
            'name' => $campaign->name,
            'project_id' => $campaign->project_id,
            'project_name' => $campaign->project?->name ?? '–',
            'visitors' => $visitors,
            'reg' => $reg,
            'net' => $net,
            'spend' => $spend,
            'net_profit' => $net - $spend,
            'roas' => $spend > 0 ? round($net / $spend, 2) : null,
            'cac' => $cac,
            ...$this->subscriptionKpisFor($filters, $campaign, $cac),
        ];
    }

    /**
     * LTV:CAC and CAC-payback for one campaign. Both need the campaign's subscription
     * economics and the CAC; either is null when the campaign's project does not track
     * subscriptions, has no CAC (no registrations), or has no subscribers. A churn-free
     * window yields a null LTV (no lifetime to estimate) but a valid payback (ARPS still
     * known).
     *
     * Only CAC is range-scoped (it is an acquisition cost). The economics half is
     * deliberately measured over a fixed trailing window, not the UI range: LTV is a
     * forward lifetime projection and ARPS a current snapshot, so binding them to the
     * selected range would make both KPIs drift with range length even when the
     * underlying unit economics are unchanged.
     *
     * @return array{ltv_cac: float|null, cac_payback: float|null}
     */
    private function subscriptionKpisFor(StatsFilterDto $base, Campaign $campaign, ?int $cac): array
    {
        $empty = ['ltv_cac' => null, 'cac_payback' => null];

        if ($cac === null || ! $campaign->project?->hasUsages([Project::USAGE_SUBSCRIPTION])) {
            return $empty;
        }

        $economics = $this->statsDetailsService->getSubscriptionEconomics($this->economicsFilter($base, $campaign));

        if ($economics === null) {
            return $empty;
        }

        return [
            'ltv_cac' => $economics['ltv'] !== null && $cac > 0 ? round($economics['ltv'] / $cac, 2) : null,
            'cac_payback' => $economics['arps'] > 0 ? round($cac / $economics['arps'], 1) : null,
        ];
    }

    private function economicsFilter(StatsFilterDto $base, Campaign $campaign): StatsFilterDto
    {
        $scoped = clone $base;
        $scoped->campaign = $campaign->id;
        $scoped->projectId = $campaign->project_id;
        $scoped->rangeType = StatsRangeType::MONTH;
        $scoped->rangeAmount = self::ECONOMICS_TRAILING_MONTHS;
        $scoped->rangeStart = now();

        return $scoped;
    }

    /**
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    private function resolveWindow(StatsFilterDto $filters): array
    {
        $conditions = StatsRangeType::conditions($filters->rangeStart, $filters->rangeAmount)[$filters->rangeType->value];

        return [
            CarbonImmutable::parse($conditions['startDateStart']),
            CarbonImmutable::parse($conditions['endDateEnd']),
        ];
    }
}
