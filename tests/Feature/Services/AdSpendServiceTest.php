<?php

use App\DTOs\StatsFilterDto;
use App\Enums\StatsRangeType;
use App\Models\AdSpend;
use App\Models\Project;
use App\Models\User;
use App\Services\AdSpendService;
use Carbon\CarbonImmutable;
use Database\Seeders\TestUserSeeder;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    CarbonImmutable::setTestNow('2026-06-21 12:00:00');

    $this->seed(TestUserSeeder::class);
    $this->user = User::where('email', config('app.test.user')['email'])->firstOrFail();
    $this->project = Project::first();
    $this->team = $this->project->team;
    $this->pid = $this->project->id;

    DB::table('stats_payments')->delete();
    DB::table('stats_users')->delete();
    DB::table('ad_spends')->delete();
    DB::table('stats_track_campaigns')->delete();
    DB::table('campaigns')->delete();

    auth()->setUser($this->user);
    Context::add('team_id', $this->team->id);

    $this->service = app(AdSpendService::class);
});

afterEach(function () {
    CarbonImmutable::setTestNow();
});

function makeCampaign(int $teamId, int $projectId, string $name): int
{
    return DB::table('campaigns')->insertGetId([
        'team_id' => $teamId,
        'project_id' => $projectId,
        'name' => $name,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

function makeTrackCampaign(int $teamId, string $name, ?int $campaignId = null): int
{
    return DB::table('stats_track_campaigns')->insertGetId([
        'team_id' => $teamId,
        'name' => $name,
        'campaign_id' => $campaignId,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

function makeCampaignUser(string $id, int $teamId, int $projectId, int $trackCampaignId, string $time): void
{
    DB::table('stats_users')->insert([
        'id' => $id,
        'team_id' => $teamId,
        'project_id' => $projectId,
        'stats_track_campaign_id' => $trackCampaignId,
        'time' => $time,
        'time_utc' => $time,
    ]);
}

function makeCampaignPayment(string $id, string $userId, int $teamId, int $projectId, float $net, string $time): void
{
    DB::table('stats_payments')->insert([
        'id' => $id,
        'stats_user_id' => $userId,
        'team_id' => $teamId,
        'project_id' => $projectId,
        'gross' => $net,
        'net' => $net,
        'currency' => 'EUR',
        'counter' => 1,
        'time' => $time,
        'time_utc' => $time,
    ]);
}

function rangeFilter(int $projectId, int $rangeAmount, string $rangeStart): StatsFilterDto
{
    return new StatsFilterDto(
        projectId: $projectId, rangeAmount: $rangeAmount, rangeType: StatsRangeType::DAY,
        rangeStart: CarbonImmutable::parse($rangeStart), comparison: '0',
    );
}

it('allocates a spend entry proportionally by day overlap', function () {
    $spend = new AdSpend(['amount' => 3000, 'starts_on' => '2026-06-01', 'ends_on' => '2026-06-30']);

    // 30-day run, 100 cents/day; window covers 10 of those days
    expect($spend->allocatedFor(CarbonImmutable::parse('2026-06-11'), CarbonImmutable::parse('2026-06-20')))->toBe(1000)
        // window fully contains the run -> whole budget
        ->and($spend->allocatedFor(CarbonImmutable::parse('2026-05-01'), CarbonImmutable::parse('2026-07-01')))->toBe(3000)
        // no overlap -> nothing
        ->and($spend->allocatedFor(CarbonImmutable::parse('2026-07-02'), CarbonImmutable::parse('2026-07-10')))->toBe(0);
});

it('computes roas, cac and net profit per campaign for the range', function () {
    $campaign = makeCampaign($this->team->id, $this->pid, 'Google Ads');
    $utm = makeTrackCampaign($this->team->id, 'google_ads', $campaign);
    makeCampaignUser('u1', $this->team->id, $this->pid, $utm, '2026-06-15 10:00:00');
    makeCampaignPayment('p1', 'u1', $this->team->id, $this->pid, 10000, '2026-06-15 10:00:00');

    AdSpend::factory()->create([
        'team_id' => $this->team->id,
        'campaign_id' => $campaign,
        'amount' => 5000,
        'starts_on' => '2026-06-01',
        'ends_on' => '2026-06-20',
    ]);

    $rows = $this->service->getCampaignProfitability(rangeFilter($this->pid, 30, '2026-06-21'));

    expect($rows)->toHaveCount(1);
    expect($rows[0])->toMatchArray([
        'campaign_id' => $campaign,
        'net' => 10000,
        'spend' => 5000,
        'reg' => 1,
        'net_profit' => 5000,
        'roas' => 2.0,
        'cac' => 5000,
    ]);
});

it('lists a campaign with spend but no revenue as a pure loss', function () {
    $campaign = makeCampaign($this->team->id, $this->pid, 'Wasted Budget');

    AdSpend::factory()->create([
        'team_id' => $this->team->id,
        'campaign_id' => $campaign,
        'amount' => 3000,
        'starts_on' => '2026-06-01',
        'ends_on' => '2026-06-20',
    ]);

    $rows = $this->service->getCampaignProfitability(rangeFilter($this->pid, 30, '2026-06-21'));

    expect($rows)->toHaveCount(1);
    expect($rows[0])->toMatchArray([
        'campaign_id' => $campaign,
        'net' => 0,
        'spend' => 3000,
        'net_profit' => -3000,
        'roas' => 0.0,
        'cac' => null,
    ]);
});

it('lists a freshly created campaign even without spend or revenue', function () {
    $campaign = makeCampaign($this->team->id, $this->pid, 'Pre-launch');

    $rows = $this->service->getCampaignProfitability(rangeFilter($this->pid, 30, '2026-06-21'));

    expect($rows)->toHaveCount(1);
    expect($rows[0])->toMatchArray([
        'campaign_id' => $campaign,
        'net' => 0,
        'spend' => 0,
        'net_profit' => 0,
        'roas' => null,
        'cac' => null,
    ]);
});

it('leaves roas null for revenue without recorded spend', function () {
    $campaign = makeCampaign($this->team->id, $this->pid, 'Organic-ish');
    $utm = makeTrackCampaign($this->team->id, 'organic_ish', $campaign);
    makeCampaignUser('u1', $this->team->id, $this->pid, $utm, '2026-06-15 10:00:00');
    makeCampaignPayment('p1', 'u1', $this->team->id, $this->pid, 8000, '2026-06-15 10:00:00');

    $rows = $this->service->getCampaignProfitability(rangeFilter($this->pid, 30, '2026-06-21'));

    expect($rows[0]['roas'])->toBeNull()
        ->and($rows[0]['net_profit'])->toBe(8000);
});

it('bundles revenue from multiple linked utm campaigns and sums spend entries', function () {
    $campaign = makeCampaign($this->team->id, $this->pid, 'Meta Ads');
    $utmA = makeTrackCampaign($this->team->id, 'meta_ads_a', $campaign);
    $utmB = makeTrackCampaign($this->team->id, 'meta_ads_b', $campaign);
    makeCampaignUser('u1', $this->team->id, $this->pid, $utmA, '2026-06-15 10:00:00');
    makeCampaignPayment('p1', 'u1', $this->team->id, $this->pid, 12000, '2026-06-15 10:00:00');
    makeCampaignUser('u2', $this->team->id, $this->pid, $utmB, '2026-06-16 10:00:00');
    makeCampaignPayment('p2', 'u2', $this->team->id, $this->pid, 8000, '2026-06-16 10:00:00');

    AdSpend::factory()->create([
        'team_id' => $this->team->id, 'campaign_id' => $campaign,
        'amount' => 4000, 'starts_on' => '2026-06-01', 'ends_on' => '2026-06-10',
    ]);
    AdSpend::factory()->create([
        'team_id' => $this->team->id, 'campaign_id' => $campaign,
        'amount' => 6000, 'starts_on' => '2026-06-11', 'ends_on' => '2026-06-20',
    ]);

    $rows = $this->service->getCampaignProfitability(rangeFilter($this->pid, 30, '2026-06-21'));

    expect($rows[0]['net'])->toBe(20000)
        ->and($rows[0]['spend'])->toBe(10000)
        ->and($rows[0]['roas'])->toBe(2.0);
});
