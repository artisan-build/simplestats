<?php

namespace App\Http\DataProviders;

use App\DTOs\StatsFilterDto;
use App\Enums\StatsRangeType;
use App\Models\Team;
use App\Services\StatsService;

class StatsIndexDataProvider extends AbstractStatsDataProvider
{
    public function toArray(): array
    {
        $user = $this->request->user();
        $effective = $this->resolveEffectiveRequest($user);

        $filters = StatsFilterDto::fromRequest($effective);

        $teamStats = $this->loadTeamStats($user, $filters);
        $teamStats = $this->buildStats($teamStats, $filters);

        return array_merge($this->commonProps($user, $filters, $teamStats), [
            'mrr' => $this->buildMrrSnapshot($teamStats, $filters),
            'mrr_series' => $this->buildMrrTimeSeries($teamStats, $filters),
        ]);
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
