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
    ) {
    }

    public function getStats(StatsFilterDto $filters)
    {
        $stats = $this->getRaw($filters);
        $this->applyStatsDetails($stats);

        return $stats;
    }

    public function getRaw(StatsFilterDto $filters): array
    {
        return $this->statsAggregationsService->getAggregatedData($filters)->toArray();
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

    public function getAllFilterFor(string $type): array
    {
        return [
            'id' => StatsFilterDto::TRACK_ALL,
            'name' => 'All',
            'usage' => StatsService::getTrackingUsageBitsCount(),
        ];
    }
}
