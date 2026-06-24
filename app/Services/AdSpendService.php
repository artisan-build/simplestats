<?php

namespace App\Services;

use App\DTOs\StatsFilterDto;
use App\Enums\StatsRangeType;
use App\Models\AdSpend;
use App\Models\Campaign;
use App\Models\StatsTrackCampaign;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class AdSpendService
{
    private const CAMPAIGN_LIMIT = 100;

    public function __construct(
        private StatsDetailsService $statsDetailsService,
    ) {}

    /**
     * Per-campaign profitability for the selected range. Each user-managed campaign
     * bundles one or more tracked utm_campaign dimensions; its revenue is the sum of
     * those dimensions' range-scoped revenue, its spend the overlap-allocated budget.
     * All campaigns are listed (even at zero), so a freshly created one is visible.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getCampaignProfitability(StatsFilterDto $filters): array
    {
        [$windowStart, $windowEnd] = $this->resolveWindow($filters);

        $revenueByUtm = $this->revenueByUtm($filters);

        return $this->campaignsForFilter($filters)
            ->map(fn (Campaign $campaign) => $this->buildRow($campaign, $revenueByUtm, $windowStart, $windowEnd))
            ->values()
            ->toArray();
    }

    /**
     * @return array<string, mixed>
     */
    public function campaignSummary(StatsFilterDto $filters, Campaign $campaign): array
    {
        [$windowStart, $windowEnd] = $this->resolveWindow($filters);

        $campaign->loadMissing(['statsTrackCampaigns:id,campaign_id,name', 'adSpends', 'project:id,name']);

        return $this->buildRow($campaign, $this->revenueByUtm($filters), $windowStart, $windowEnd);
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
     * @return Collection<int, Campaign>
     */
    private function campaignsForFilter(StatsFilterDto $filters): Collection
    {
        return Campaign::query()
            ->with(['statsTrackCampaigns:id,campaign_id,name', 'adSpends', 'project:id,name'])
            ->when(
                $filters->projectId !== StatsFilterDto::TRACK_ALL,
                fn ($query) => $query->where('project_id', $filters->projectId),
            )
            ->orderByDesc('created_at')
            ->get();
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
    private function buildRow(Campaign $campaign, Collection $revenueByUtm, CarbonImmutable $windowStart, CarbonImmutable $windowEnd): array
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
            'cac' => $reg > 0 ? (int) round($spend / $reg) : null,
        ];
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
