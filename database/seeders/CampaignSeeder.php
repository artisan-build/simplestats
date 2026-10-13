<?php

namespace Database\Seeders;

use App\DTOs\StatsFilterDto;
use App\Enums\StatsRangeType;
use App\Models\AdSpend;
use App\Models\Campaign;
use App\Models\Project;
use App\Models\StatsTrackCampaign;
use App\Models\StatsUser;
use App\Models\StatsVisitor;
use App\Models\User;
use App\Services\StatsDetailsService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Str;

class CampaignSeeder extends Seeder
{
    private const int MONTHS_BACK = 6;

    private const int CAMPAIGNS_PER_PROJECT = 5;

    /**
     * Target ROAS each demo campaign is sized to, applied by campaign position so
     * every project shows a deterministic spread: clearly profitable (> 1), around
     * break-even, and loss-making campaigns. Spend drives CAC too (higher target ROAS
     * means lower spend, hence lower CAC); LTV:CAC and payback then layer each
     * campaign's own subscription economics on top, so they vary independently of ROAS.
     */
    private const array TARGET_ROAS = [4.0, 2.0, 1.0, 0.6, 0.35];

    /**
     * Demo marketing campaigns: for each project, wrap a few of the utm_campaign
     * values it actually drove traffic through into a named Campaign, link the utm
     * dimension to it, and add monthly ad spend sized to a per-campaign target ROAS
     * against that campaign's real revenue, so the Campaigns page shows a clear mix
     * of profitable and loss-making campaigns (with matching CAC, LTV:CAC, payback).
     */
    public function run(): void
    {
        $statsDetails = resolve(StatsDetailsService::class);

        Project::with('team:id,currency')->get()->each(function (Project $project) use ($statsDetails): void {
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

            // utm dimensions are team-wide; a dimension already claimed by another
            // project's campaign stays there (a dimension maps to a single campaign),
            // so each demo campaign owns a distinct utm and shows real revenue.
            $linked = StatsTrackCampaign::whereIn('id', $utmIds)
                ->whereNull('campaign_id')
                ->get()
                ->values()
                ->map(function (StatsTrackCampaign $utm) use ($project): array {
                    $campaign = Campaign::firstOrCreate([
                        'team_id' => $project->team_id,
                        'project_id' => $project->id,
                        'name' => Str::headline($utm->name),
                    ]);

                    $utm->update(['campaign_id' => $campaign->id]);

                    return ['campaign' => $campaign, 'utm' => $utm];
                });

            $this->seedSpend($project, $linked, $statsDetails);
        });
    }

    /**
     * Size each campaign's spend against the revenue the Campaigns page itself
     * reports (same grouped track_campaign aggregation), so the displayed ROAS lands
     * on the campaign's target. Computing it ourselves in SQL diverges from the
     * page's attribution, hence reusing the service.
     *
     * @param  Collection<int, array{campaign: Campaign, utm: StatsTrackCampaign}>  $linked
     */
    private function seedSpend(Project $project, Collection $linked, StatsDetailsService $statsDetails): void
    {
        // The grouped aggregation resolves the team from context/auth; without it the
        // team scope would be empty (CLI has no authenticated user).
        Context::add('team_id', $project->team_id);

        if ($member = User::where('current_team_id', $project->team_id)->first()) {
            auth()->setUser($member);
        }

        $filter = new StatsFilterDto(
            projectId: $project->id,
            rangeAmount: self::MONTHS_BACK,
            rangeType: StatsRangeType::MONTH,
            rangeStart: now(),
            comparison: '0',
        );

        $netByUtm = collect($statsDetails->getGroupedData($filter, 'track_campaign', self::CAMPAIGNS_PER_PROJECT * 4, 'net'))
            ->keyBy(fn (array $row): int => (int) ($row['type_id'] ?? 0))
            ->map(fn (array $row): float => (float) ($row['net'] ?? 0));

        $currency = $project->team->currency ?? 'EUR';
        $rows = [];

        foreach ($linked as $index => $item) {
            $net = $netByUtm->get($item['utm']->id, 0.0);

            if ($net <= 0) {
                continue;
            }

            $targetRoas = self::TARGET_ROAS[$index % count(self::TARGET_ROAS)];
            array_push($rows, ...$this->monthlySpendRows($item['campaign'], (int) round($net / $targetRoas), $currency));
        }

        if ($rows !== []) {
            AdSpend::insert($rows);
        }
    }

    /**
     * Split a campaign's total spend into one ad-spend entry per month so the spend
     * detail view has a realistic monthly breakdown. The remainder lands on the most
     * recent month to keep the sum exact.
     *
     * @return array<int, array<string, mixed>>
     */
    private function monthlySpendRows(Campaign $campaign, int $totalSpend, string $currency): array
    {
        $perMonth = intdiv($totalSpend, self::MONTHS_BACK);
        $remainder = $totalSpend - $perMonth * self::MONTHS_BACK;
        $rows = [];

        for ($month = 0; $month < self::MONTHS_BACK; $month++) {
            $period = now()->subMonths($month);

            $rows[] = [
                // Bulk insert skips model events, so the uuid must be set here.
                'uuid' => (string) Str::uuid(),
                'team_id' => $campaign->team_id,
                'campaign_id' => $campaign->id,
                'amount' => $perMonth + ($month === 0 ? $remainder : 0),
                'currency' => $currency,
                'starts_on' => $period->startOfMonth()->toDateString(),
                'ends_on' => $period->endOfMonth()->toDateString(),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        return $rows;
    }
}
