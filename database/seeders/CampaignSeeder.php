<?php

namespace Database\Seeders;

use App\Models\AdSpend;
use App\Models\Campaign;
use App\Models\Project;
use App\Models\StatsTrackCampaign;
use App\Models\StatsUser;
use App\Models\StatsVisitor;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CampaignSeeder extends Seeder
{
    private const MONTHS_BACK = 6;

    private const CAMPAIGNS_PER_PROJECT = 5;

    /**
     * Demo marketing campaigns: for each project, wrap a few of the utm_campaign
     * values it actually drove traffic through into a named Campaign, link the utm
     * dimension to it, and add monthly ad spend, so the Campaigns page shows real
     * ROAS / CAC / net profit (a mix of profitable and loss-making campaigns).
     */
    public function run(): void
    {
        Project::with('team:id,currency')->get()->each(function (Project $project) {
            $utmIds = StatsUser::where('project_id', $project->id)
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

            if ($utmIds->isEmpty()) {
                return;
            }

            $currency = $project->team->currency ?? 'EUR';

            // utm dimensions are team-wide; a dimension already claimed by another
            // project's campaign stays there (a dimension maps to a single campaign),
            // so each demo campaign owns a distinct utm and shows real revenue.
            $utms = StatsTrackCampaign::whereIn('id', $utmIds)->whereNull('campaign_id')->get();

            foreach ($utms as $utm) {
                $campaign = Campaign::firstOrCreate([
                    'team_id' => $project->team_id,
                    'project_id' => $project->id,
                    'name' => Str::headline($utm->name),
                ]);

                $utm->update(['campaign_id' => $campaign->id]);

                $this->seedSpend($campaign, $currency);
            }
        });
    }

    private function seedSpend(Campaign $campaign, string $currency): void
    {
        $rows = [];

        for ($month = 0; $month < self::MONTHS_BACK; $month++) {
            $period = now()->subMonths($month);

            $rows[] = [
                'team_id' => $campaign->team_id,
                'campaign_id' => $campaign->id,
                'amount' => rand(4000, 40000),
                'currency' => $currency,
                'starts_on' => $period->startOfMonth()->toDateString(),
                'ends_on' => $period->endOfMonth()->toDateString(),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        AdSpend::insert($rows);
    }
}
