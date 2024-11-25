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
            if (isset($stat['pd_REG'])) {
                $stat['pd_CR'] = round($this->statsService->safeDivision($stat['pd_REG'], $stat['pd_VISITOR']) * 100, 2);
                $stat['pd_ARPDAU'] = $this->statsService->safeDivision($stat['pd_NET'], $stat['pd_DAU']);
                $stat['lt_REG2DAU'] = round($this->statsService->safeDivision($stat['pd_DAU'], $stat['lt_REG']) * 100, 2);
                $stat['lt_REG2PU'] = round($this->statsService->safeDivision($stat['lt_PU'], $stat['lt_REG']) * 100, 2);
                $stat['lt_REG2TAU'] = round($this->statsService->safeDivision($stat['lt_TAU'], $stat['lt_REG']) * 100, 2);
                $stat['lt_ARPU'] = round($this->statsService->safeDivision($stat['lt_NET'], $stat['lt_REG']), 2);
                $stat['lt_ARPPU'] = round($this->statsService->safeDivision($stat['lt_NET'], $stat['lt_PU']), 2);
            }

            $stat['pd_FEE'] = $stat['pd_GROSS'] - $stat['pd_NET'];
            $stat['lt_ARPV'] = round($this->statsService->safeDivision($stat['lt_NET'], $stat['lt_VISITOR']), 2);
        } unset($stat);
    }

    public function getRangeTypes(): Collection
    {
        return collect(StatsRangeType::values())->transform(fn($name, $key) => [
            'id' => $name,
            'name' => ucfirst($name)
        ]);
    }

    public function getAllFilterFor(string $type): array
    {
        return [
            'id' => StatsFilterDto::TRACK_ALL,
            'name' => 'All',
            'usage' => StatsService::getTrackingUsageBitsCount()
        ];
    }
}
