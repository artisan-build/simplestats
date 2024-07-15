<?php

namespace App\Services;

use App\DTOs\StatsFilterDto;
use App\Enums\StatsRangeType;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class StatsDetailsService
{
    public function __construct(
        private StatsService $statsService,
        private StatsAggregationsService $statsAggregationsService,
    ) {}

    public function getStats(StatsFilterDto $filters)
    {
        $stats = $this->getRaw($filters);

        $this->groupStats($stats);
        $this->applyStatsTotal($stats);

        $this->applyStatsDetails($stats);

        return $stats;
    }

    public function getRaw(StatsFilterDto $filters): array
    {
        return $this->statsAggregationsService->getAggregatedData($filters)->toArray();
    }

    private function groupStats(array &$stats): void
    {
        foreach ($stats as &$stat) {
            $stat = $this->statsService->groupStats($stat);

            $stat['stats']['date'] = $stat['date'];
            unset($stat['date']);
            $stat['stats']['per_date']['CR'] = round($this->statsService->safeDivision($stat['stats']['per_date']['REG'], $stat['stats']['per_date']['VISITOR']) * 100, 2);
            $stat['stats']['per_date']['FEE'] = $stat['stats']['per_date']['GROSS'] - $stat['stats']['per_date']['NET'];
            $stat['stats']['per_date']['ARPDAU'] = $this->statsService->safeDivision($stat['stats']['per_date']['NET'], $stat['stats']['per_date']['DAU']);
        } unset($stat);
    }

    private function applyStatsTotal(array &$stats): void
    {
        $statsTotal = [];

        foreach ($stats as $stat) {
            $this->statsService->calcStatsTotal(
                stats: $stat['stats'],
                totalStats: $statsTotal,
                skipCategories: ['lifetime']
            );
        }

        $statsTotal['date'] = 'Total';
        $stats['total']['stats'] = $statsTotal;
    }

    private function applyStatsDetails(array &$stats): void
    {
        foreach ($stats as &$stat) {
            $stat['stats'] = $this->statsService->addStatsDetails($stat['stats']);
        } unset($stat);
    }

    public function getRangeTypes(): Collection
    {
        return collect(StatsRangeType::values())->transform(fn($name, $key) => [
            'id' => $name, 'name' => ucfirst($name)
        ]);
    }

    public function getAllFilterFor(string $type): array
    {
        return ['id' => StatsFilterDto::TRACK_ALL, 'name' => 'All'];//.$type];
    }

    public function getOrganicFilter(string $name = 'Direct / Unknown'): array
    {
        return ['id' => StatsFilterDto::TRACK_ORGANIC, 'name' => $name];
    }
}
