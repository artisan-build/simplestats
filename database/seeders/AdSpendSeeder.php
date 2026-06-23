<?php

namespace Database\Seeders;

use App\Models\AdSpend;
use App\Models\Project;
use App\Models\StatsUser;
use App\Models\StatsVisitor;
use Illuminate\Database\Seeder;

class AdSpendSeeder extends Seeder
{
    private const MONTHS_BACK = 6;

    private const CAMPAIGNS_PER_PROJECT = 5;

    /**
     * Demo ad spend: monthly budgets for the campaigns a project actually drove
     * traffic through, so the Campaigns page shows real ROAS / CAC / net profit
     * (the spend joins onto the existing per-campaign revenue attribution).
     */
    public function run(): void
    {
        Project::with('team:id,currency')->get()->each(function (Project $project) {
            $campaignIds = StatsUser::where('project_id', $project->id)
                ->whereNotNull('stats_track_campaign_id')
                ->distinct()
                ->pluck('stats_track_campaign_id')
                ->merge(
                    StatsVisitor::where('project_id', $project->id)
                        ->whereNotNull('stats_track_campaign_id')
                        ->distinct()
                        ->pluck('stats_track_campaign_id'),
                )
                ->unique()
                ->take(self::CAMPAIGNS_PER_PROJECT);

            if ($campaignIds->isEmpty()) {
                return;
            }

            $currency = $project->team->currency ?? 'EUR';
            $rows = [];

            foreach ($campaignIds as $campaignId) {
                for ($month = 0; $month < self::MONTHS_BACK; $month++) {
                    $period = now()->subMonths($month);

                    $rows[] = [
                        'team_id' => $project->team_id,
                        'project_id' => $project->id,
                        'stats_track_campaign_id' => $campaignId,
                        'amount' => rand(4000, 40000),
                        'currency' => $currency,
                        'starts_on' => $period->startOfMonth()->toDateString(),
                        'ends_on' => $period->endOfMonth()->toDateString(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }

            AdSpend::insert($rows);
        });
    }
}
