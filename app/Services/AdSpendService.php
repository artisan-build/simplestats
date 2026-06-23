<?php

namespace App\Services;

use App\DTOs\StatsFilterDto;
use App\Enums\StatsRangeType;
use App\Models\AdSpend;
use App\Models\StatsTrackCampaign;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class AdSpendService
{
    private const CAMPAIGN_LIMIT = 100;

    public function __construct(
        private StatsDetailsService $statsDetailsService,
    ) {}

    /**
     * Per-campaign profitability for the selected range: range-scoped revenue
     * (reused from the grouped track_campaign aggregation) merged with the
     * overlap-allocated ad spend. Sorted by net profit, newest losses first.
     *
     * @return array<int, array{campaign_id: int, name: string, visitors: int, reg: int, net: int, spend: int, net_profit: int, roas: float|null, cac: int|null}>
     */
    public function getCampaignProfitability(StatsFilterDto $filters): array
    {
        [$windowStart, $windowEnd] = $this->resolveWindow($filters);

        $spendByCampaign = $this->spendByCampaign($filters, $windowStart, $windowEnd);

        $rows = collect($this->statsDetailsService->getGroupedData($filters, 'track_campaign', self::CAMPAIGN_LIMIT, 'net'))
            ->map(function (array $row) use (&$spendByCampaign) {
                $campaignId = (int) ($row['type_id'] ?? 0);
                $spend = (int) $spendByCampaign->pull($campaignId, 0);

                return $this->withMetrics($campaignId, $row['name'], $row, $spend);
            });

        return $rows->concat($this->spendOnlyRows($spendByCampaign))
            ->sortByDesc('net_profit')
            ->values()
            ->toArray();
    }

    /**
     * Spend entries for the management table, scoped to the selected project
     * (or all of the team's when no specific project is filtered).
     *
     * @return array<int, array<string, mixed>>
     */
    public function listForFilter(StatsFilterDto $filters): array
    {
        return AdSpend::query()
            ->with('statsTrackCampaign:id,name')
            ->when(
                $filters->projectId !== StatsFilterDto::TRACK_ALL,
                fn ($query) => $query->where('project_id', $filters->projectId),
            )
            ->orderByDesc('starts_on')
            ->get()
            ->map(fn (AdSpend $spend) => [
                'id' => $spend->id,
                'project_id' => $spend->project_id,
                'stats_track_campaign_id' => $spend->stats_track_campaign_id,
                'campaign_name' => $spend->statsTrackCampaign?->name ?? 'Unknown',
                'amount' => $spend->amount,
                'currency' => $spend->currency,
                'starts_on' => $spend->starts_on->toDateString(),
                'ends_on' => $spend->ends_on->toDateString(),
            ])
            ->toArray();
    }

    /**
     * @return array<int, array{id: int, name: string}>
     */
    public function availableCampaigns(): array
    {
        return StatsTrackCampaign::orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (StatsTrackCampaign $campaign) => [
                'id' => $campaign->id,
                'name' => $campaign->name,
            ])
            ->toArray();
    }

    /**
     * @return Collection<int, int> campaign id => allocated cents
     */
    private function spendByCampaign(StatsFilterDto $filters, CarbonImmutable $windowStart, CarbonImmutable $windowEnd): Collection
    {
        return AdSpend::query()
            ->where('ends_on', '>=', $windowStart->toDateString())
            ->where('starts_on', '<=', $windowEnd->toDateString())
            ->when(
                $filters->projectId !== StatsFilterDto::TRACK_ALL,
                fn ($query) => $query->where('project_id', $filters->projectId),
            )
            ->get()
            ->groupBy('stats_track_campaign_id')
            ->map(fn (Collection $entries) => (int) $entries->sum(fn (AdSpend $spend) => $spend->allocatedFor($windowStart, $windowEnd)));
    }

    /**
     * Campaigns with spend but zero revenue/regs in the range are dropped by the
     * grouped query's all-zero reject, yet a pure loss is exactly what this page
     * must surface, so re-add them here.
     *
     * @param  Collection<int, int>  $spendByCampaign
     * @return Collection<int, array<string, mixed>>
     */
    private function spendOnlyRows(Collection $spendByCampaign): Collection
    {
        if ($spendByCampaign->isEmpty()) {
            return collect();
        }

        $names = StatsTrackCampaign::whereIn('id', $spendByCampaign->keys())->pluck('name', 'id');

        return $spendByCampaign->map(function (int $spend, int $campaignId) use ($names) {
            return $this->withMetrics($campaignId, $names[$campaignId] ?? 'Unknown', [], $spend);
        })->values();
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function withMetrics(int $campaignId, string $name, array $row, int $spend): array
    {
        $net = (int) round($row['net'] ?? 0);
        $reg = (int) ($row['reg'] ?? 0);

        return [
            'campaign_id' => $campaignId,
            'name' => $name,
            'visitors' => (int) ($row['visitors'] ?? 0),
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
