<?php

use App\DTOs\StatsFilterDto;
use App\Enums\StatsRangeType;
use App\Models\Campaign;
use App\Models\Project;
use App\Models\StatsAggregation;
use App\Models\StatsTrackCampaign;
use App\Models\User;
use App\Services\CampaignService;
use App\Services\StatsAggregationsService;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Database\Seeders\TestUserSeeder;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->seed(TestUserSeeder::class);
    $this->user = User::where('email', config('app.test.user')['email'])->firstOrFail();
    $this->project = Project::first();
    $this->team = $this->project->team;
    $this->pid = $this->project->id;

    DB::table('stats_visitors')->delete();

    auth()->setUser($this->user);
    Context::add('team_id', $this->team->id);
    config(['app.stats_aggregation_enabled' => true]);
});

afterEach(function () {
    Carbon::setTestNow();
});

function makeFilterTrackCampaign(int $teamId, string $name, ?int $campaignId): int
{
    return DB::table('stats_track_campaigns')->insertGetId([
        'team_id' => $teamId,
        'name' => $name,
        'campaign_id' => $campaignId,
        'created_at' => '2026-03-28 12:00:00',
        'updated_at' => '2026-03-28 12:00:00',
    ]);
}

it('narrows aggregated stats to every utm dimension the campaign owns', function () {
    $campaign = Campaign::create(['team_id' => $this->team->id, 'project_id' => $this->pid, 'name' => 'Summer Sale']);

    $linkedA = makeFilterTrackCampaign($this->team->id, 'summer_a', $campaign->id);
    $linkedB = makeFilterTrackCampaign($this->team->id, 'summer_b', $campaign->id);
    $unrelated = makeFilterTrackCampaign($this->team->id, 'unrelated', null);

    $insert = function (int $count, ?int $trackCampaignId) {
        for ($i = 0; $i < $count; $i++) {
            DB::table('stats_visitors')->insert([
                'team_id' => $this->team->id,
                'project_id' => $this->pid,
                'visitor_hash' => Str::uuid()->toString(),
                'stats_track_campaign_id' => $trackCampaignId,
                'time' => '2026-03-30 12:00:00',
                'time_utc' => '2026-03-30 12:00:00',
            ]);
        }
    };

    $insert(4, $linkedA);
    $insert(3, $linkedB);
    $insert(5, $unrelated);
    $insert(2, null);

    $filters = new StatsFilterDto(
        projectId: $this->pid, rangeAmount: 1, rangeType: StatsRangeType::DAY,
        rangeStart: CarbonImmutable::parse('2026-03-30'), comparison: '0',
        campaign: $campaign->id,
    );

    $data = app(StatsAggregationsService::class)->getAggregatedData($filters);

    expect($data['2026-03-30']['pd_visitor'])->toBe(7);
});

it('returns zero stats for a campaign with no linked utm dimensions', function () {
    $campaign = Campaign::create(['team_id' => $this->team->id, 'project_id' => $this->pid, 'name' => 'Empty']);

    $unrelated = makeFilterTrackCampaign($this->team->id, 'unrelated', null);

    for ($i = 0; $i < 6; $i++) {
        DB::table('stats_visitors')->insert([
            'team_id' => $this->team->id,
            'project_id' => $this->pid,
            'visitor_hash' => Str::uuid()->toString(),
            'stats_track_campaign_id' => $unrelated,
            'time' => '2026-03-30 12:00:00',
            'time_utc' => '2026-03-30 12:00:00',
        ]);
    }

    $filters = new StatsFilterDto(
        projectId: $this->pid, rangeAmount: 1, rangeType: StatsRangeType::DAY,
        rangeStart: CarbonImmutable::parse('2026-03-30'), comparison: '0',
        campaign: $campaign->id,
    );

    $data = app(StatsAggregationsService::class)->getAggregatedData($filters);

    expect($data['2026-03-30']['pd_visitor'])->toBe(0);
});

it('drops cached campaign aggregations when a utm link is removed', function () {
    $campaign = Campaign::create(['team_id' => $this->team->id, 'project_id' => $this->pid, 'name' => 'Cached']);
    $linkedId = makeFilterTrackCampaign($this->team->id, 'cached_utm', $campaign->id);

    DB::table('stats_visitors')->insert([
        'team_id' => $this->team->id,
        'project_id' => $this->pid,
        'visitor_hash' => Str::uuid()->toString(),
        'stats_track_campaign_id' => $linkedId,
        'time' => '2026-03-30 12:00:00',
        'time_utc' => '2026-03-30 12:00:00',
    ]);

    $filters = new StatsFilterDto(
        projectId: $this->pid, rangeAmount: 1, rangeType: StatsRangeType::DAY,
        rangeStart: CarbonImmutable::parse('2026-03-30'), comparison: '0',
        campaign: $campaign->id,
    );

    app(StatsAggregationsService::class)->getAggregatedData($filters);

    expect(StatsAggregation::where('filter_hash', $filters->makeHash())->count())->toBeGreaterThan(0);

    app(CampaignService::class)->unlinkTrackCampaign($campaign, StatsTrackCampaign::find($linkedId));

    expect(StatsAggregation::where('filter_hash', $filters->makeHash())->count())->toBe(0);
});

it('accepts a campaign filter on the dashboard for the current team', function () {
    $campaign = Campaign::create(['team_id' => $this->team->id, 'project_id' => $this->pid, 'name' => 'Mine']);

    $this->actingAs($this->user)
        ->get(route('dashboard', ['campaign' => $campaign->id, 'project_id' => $this->pid]))
        ->assertOk();
});

it('exposes the campaign summary on the dashboard when filtered by a campaign', function () {
    $campaign = Campaign::create(['team_id' => $this->team->id, 'project_id' => $this->pid, 'name' => 'Summary Test']);

    $this->actingAs($this->user)
        ->get(route('dashboard', ['campaign' => $campaign->id, 'project_id' => $this->pid]))
        ->assertInertia(fn ($page) => $page
            ->where('campaign_summary.name', 'Summary Test')
            ->where('campaign_summary.spend', 0)
        );
});

it('suppresses the campaign summary when another dimension filter is active', function () {
    $campaign = Campaign::create(['team_id' => $this->team->id, 'project_id' => $this->pid, 'name' => 'Filtered']);
    $utmId = makeFilterTrackCampaign($this->team->id, 'filtered_utm', $campaign->id);

    $this->actingAs($this->user)
        ->get(route('dashboard', ['campaign' => $campaign->id, 'track_campaign' => $utmId, 'project_id' => $this->pid]))
        ->assertInertia(fn ($page) => $page->where('campaign_summary', null));
});

it('has no campaign summary on the unfiltered dashboard', function () {
    $this->actingAs($this->user)
        ->get(route('dashboard', ['project_id' => $this->pid]))
        ->assertInertia(fn ($page) => $page->where('campaign_summary', null));
});

it('rejects a campaign filter referencing another team', function () {
    $otherTeamId = User::factory()->withPersonalTeam()->create()->currentTeam->id;
    $foreignCampaignId = DB::table('campaigns')->insertGetId([
        'team_id' => $otherTeamId,
        'project_id' => $this->pid,
        'name' => 'Foreign',
        'created_at' => '2026-03-28 12:00:00',
        'updated_at' => '2026-03-28 12:00:00',
    ]);

    $this->actingAs($this->user)
        ->get(route('dashboard', ['campaign' => $foreignCampaignId, 'project_id' => $this->pid]))
        ->assertSessionHasErrors('campaign');
});
