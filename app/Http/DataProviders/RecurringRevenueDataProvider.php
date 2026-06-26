<?php

namespace App\Http\DataProviders;

use App\Concerns\BuildsMrrProps;
use App\DTOs\StatsFilterDto;

class RecurringRevenueDataProvider extends AbstractStatsDataProvider
{
    use BuildsMrrProps;

    public function toArray(): array
    {
        $user = $this->request->user();
        $effective = $this->resolveEffectiveRequest($user);

        $filters = StatsFilterDto::fromRequest($effective);

        $teamStats = $this->loadTeamStats($user, $filters);

        return array_merge($this->commonProps($user, $filters, $teamStats), [
            'mrr' => $this->buildMrrSnapshot($teamStats, $filters),
            'mrr_movements' => $this->buildMrrMovements($teamStats, $filters),
            'mrr_retention' => $this->buildMrrRetention($teamStats, $filters),
            'revenue_churn' => $this->buildRevenueChurn($teamStats, $filters),
        ]);
    }
}
