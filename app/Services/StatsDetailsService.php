<?php

namespace App\Services;

use App\DTOs\StatsFilterDto;
use App\Enums\StatsRangeType;
use Illuminate\Support\Collection;

class StatsDetailsService
{
    public function __construct(
        private StatsService $statsService,
        private StatsAggregationsService $statsAggregationsService,
    ) {}

    public function getStats(StatsFilterDto $filters): array
    {
        $stats = $this->getRaw($filters);
        $this->applyStatsDetails($stats);

        return $stats;
    }

    public function getRaw(StatsFilterDto $filters): array
    {
        return $this->statsAggregationsService->getAggregatedData($filters)->toArray();
    }

    /**
     * @return array{mrr: float, arr: float, mrr_prev: float}
     */
    public function getMrrSnapshot(StatsFilterDto $filters): array
    {
        return $this->statsAggregationsService->getMrrSnapshot($filters);
    }

    /**
     * Fetch one period of grouped rows. "property" groups by the values of
     * the selected custom property, everything else by a static dimension.
     *
     * ->values()->toArray() makes sure the indexes are int-numeric and not
     * string-numeric (which would result in a js object). This happens cause
     * of the reject at the end of the aggregation methods.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getGroupedData(
        StatsFilterDto $filters,
        string $statsType,
        int $limit,
        ?string $sort = null,
        ?int $propertyNameId = null,
        bool $lifetime = false,
    ): array {
        if ($statsType === 'property') {
            return $this->statsAggregationsService->getAggregatedPropertyData(
                $filters,
                (int) $propertyNameId,
                $sort ?? 'reg',
                $limit,
                $lifetime,
            )->values()->toArray();
        }

        return $this->statsAggregationsService->getAggregatedGroupedData(
            $filters,
            $statsType,
            $sort,
            $limit,
        )->values()->toArray();
    }

    private function applyStatsDetails(array &$stats): void
    {
        foreach ($stats as &$stat) {
            if (isset($stat['pd_reg'])) {
                $stat['pd_cr'] = round($this->statsService->safeDivision($stat['pd_reg'], $stat['pd_visitor']) * 100, 2);
                $stat['pd_arpdau'] = $this->statsService->safeDivision($stat['pd_net'], $stat['pd_dau']);
                $stat['lt_reg2dau'] = round($this->statsService->safeDivision($stat['pd_dau'], $stat['lt_reg']) * 100, 2);
                $stat['lt_reg2pu'] = round($this->statsService->safeDivision($stat['lt_pu'], $stat['lt_reg']) * 100, 2);
                $stat['lt_reg2tau'] = round($this->statsService->safeDivision($stat['lt_tau'], $stat['lt_reg']) * 100, 2);
                $stat['lt_arpu'] = round($this->statsService->safeDivision($stat['lt_net'], $stat['lt_reg']), 2);
                $stat['lt_arppu'] = round($this->statsService->safeDivision($stat['lt_net'], $stat['lt_pu']), 2);
            }

            $stat['pd_gross'] = round($stat['pd_gross'], 2);
            $stat['pd_net'] = round($stat['pd_net'], 2);
            $stat['lt_net'] = round($stat['lt_net'], 2);

            $stat['pd_fee'] = $stat['pd_gross'] - $stat['pd_net'];
            $stat['lt_arpv'] = round($this->statsService->safeDivision($stat['lt_net'], $stat['lt_visitor']), 2);
            unset($stat['pd_tau'], $stat['pd_pu']);
        } unset($stat);
    }

    public function getRangeTypes(): Collection
    {
        return collect(StatsRangeType::values())->transform(fn ($name, $key) => [
            'id' => $name,
            'name' => ucfirst($name),
        ]);
    }

    public function getAllFilterFor(string $type, ?int $usage = null): array
    {
        return [
            'id' => StatsFilterDto::TRACK_ALL,
            'name' => 'All',
            'usage' => $usage ?? StatsService::getTrackingUsageBitsCount(),
        ];
    }
}
