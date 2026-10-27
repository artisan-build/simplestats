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
use App\Services\AdSpendService;
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
     * Campaigns whose spend lies long before the seeded stats, so they have no
     * activity in any recent range and show up collapsed on the Campaigns page.
     *
     * @var array<int, array{name: string, months_ago: int, amount: int}>
     */
    private const array PAST_CAMPAIGNS = [
        ['name' => 'Black Friday Retargeting', 'months_ago' => 23, 'amount' => 120000],
        ['name' => 'Podcast Sponsorship', 'months_ago' => 18, 'amount' => 45000],
    ];

    /**
     * Demo marketing campaigns: for each project, wrap a few of the utm_campaign
     * values it actually drove traffic through into a named Campaign, link the utm
     * dimension to it, and add monthly ad spend sized to a per-campaign target ROAS
     * against that campaign's real revenue, so the Campaigns page shows a clear mix
     * of profitable and loss-making campaigns (with matching CAC, LTV:CAC, payback).
     */
    public function run(): void
    {
        $adSpendService = resolve(AdSpendService::class);

        Project::with('team:id,currency')->get()->each(function (Project $project) use ($adSpendService): void {
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

            if ($linked->isEmpty()) {
                return;
            }

            $this->seedSpend($project, $linked, $adSpendService);
            $this->seedPastCampaigns($project);
        });
    }

    /**
     * Size each campaign's spend against the revenue the Campaigns page itself
     * reports (same acquisition-cohort attribution), so the displayed ROAS lands
     * on the campaign's target. Computing it ourselves in SQL diverges from the
     * page's attribution, hence reusing the service.
     *
     * Spend covers the project's whole stats history: customers acquired before
     * the first spend entry would otherwise count as free and inflate the ROAS of
     * every range reaching back that far ("All Time", "Last Year").
     *
     * @param  Collection<int, array{campaign: Campaign, utm: StatsTrackCampaign}>  $linked
     */
    private function seedSpend(Project $project, Collection $linked, AdSpendService $adSpendService): void
    {
        // The revenue query resolves the team from context/auth; without it the
        // team scope would be empty (CLI has no authenticated user).
        Context::add('team_id', $project->team_id);

        if ($member = User::where('current_team_id', $project->team_id)->first()) {
            auth()->setUser($member);
        }

        $months = $this->historyMonths($project);

        $filter = new StatsFilterDto(
            projectId: $project->id,
            rangeAmount: $months,
            rangeType: StatsRangeType::MONTH,
            rangeStart: now(),
            comparison: '0',
        );

        $currency = $project->team->currency ?? 'EUR';
        $rows = [];

        foreach ($linked as $index => $item) {
            $net = $adSpendService->campaignSummary($filter, $item['campaign'])['net'];

            if ($net <= 0) {
                continue;
            }

            $targetRoas = self::TARGET_ROAS[$index % count(self::TARGET_ROAS)];
            array_push($rows, ...$this->monthlySpendRows($item['campaign'], (int) round($net / $targetRoas), $months, $currency));
        }

        if ($rows !== []) {
            AdSpend::insert($rows);
        }
    }

    private function seedPastCampaigns(Project $project): void
    {
        foreach (self::PAST_CAMPAIGNS as $past) {
            $campaign = Campaign::firstOrCreate([
                'team_id' => $project->team_id,
                'project_id' => $project->id,
                'name' => $past['name'],
            ]);

            if ($campaign->adSpends()->exists()) {
                continue;
            }

            $period = now()->startOfMonth()->subMonths($past['months_ago']);

            // The list sorts by creation, so date the campaign to its run instead of the seed run.
            $campaign->forceFill(['created_at' => $period])->save();

            $campaign->adSpends()->create([
                'team_id' => $project->team_id,
                'amount' => $past['amount'],
                'currency' => $project->team->currency ?? 'EUR',
                'starts_on' => $period->startOfMonth()->toDateString(),
                'ends_on' => $period->endOfMonth()->toDateString(),
            ]);
        }
    }

    /**
     * Calendar months from the project's first stats up to the current month,
     * matching how a MONTH range of that length is resolved.
     */
    private function historyMonths(Project $project): int
    {
        $start = $project->stats_starting_at;

        if ($start === null) {
            return self::MONTHS_BACK;
        }

        return max(1, (int) $start->startOfMonth()->diffInMonths(now()->startOfMonth()) + 1);
    }

    /**
     * Split a campaign's total spend into one ad-spend entry per month so the spend
     * detail view has a realistic monthly breakdown. The remainder lands on the most
     * recent month to keep the sum exact.
     *
     * @return array<int, array<string, mixed>>
     */
    private function monthlySpendRows(Campaign $campaign, int $totalSpend, int $months, string $currency): array
    {
        $perMonth = intdiv($totalSpend, $months);
        $remainder = $totalSpend - $perMonth * $months;
        $rows = [];

        for ($month = 0; $month < $months; $month++) {
            $period = now()->startOfMonth()->subMonths($month);

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
