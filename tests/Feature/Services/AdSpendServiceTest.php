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
    DB::table('stats_track_campaigns')->delete();
    DB::table('ad_spends')->delete();

    auth()->setUser($this->user);
    Context::add('team_id', $this->team->id);

    $this->service = app(AdSpendService::class);
});

afterEach(function () {
    CarbonImmutable::setTestNow();
});

function makeCampaign(int $teamId, string $name): int
{
    return DB::table('stats_track_campaigns')->insertGetId([
        'team_id' => $teamId,
        'name' => $name,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

function makeCampaignUser(string $id, int $teamId, int $projectId, int $campaignId, string $time): void
{
    DB::table('stats_users')->insert([
        'id' => $id,
        'team_id' => $teamId,
        'project_id' => $projectId,
        'stats_track_campaign_id' => $campaignId,
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
    $c1 = makeCampaign($this->team->id, 'Google Ads');
    makeCampaignUser('u1', $this->team->id, $this->pid, $c1, '2026-06-15 10:00:00');
    makeCampaignPayment('p1', 'u1', $this->team->id, $this->pid, 10000, '2026-06-15 10:00:00');

    AdSpend::factory()->create([
        'project_id' => $this->pid,
        'stats_track_campaign_id' => $c1,
        'amount' => 5000,
        'starts_on' => '2026-06-01',
        'ends_on' => '2026-06-20',
    ]);

    $rows = $this->service->getCampaignProfitability(rangeFilter($this->pid, 30, '2026-06-21'));

    expect($rows)->toHaveCount(1);
    expect($rows[0])->toMatchArray([
        'campaign_id' => $c1,
        'net' => 10000,
        'spend' => 5000,
        'reg' => 1,
        'net_profit' => 5000,
        'roas' => 2.0,
        'cac' => 5000,
    ]);
});

it('surfaces a campaign with spend but no revenue as a pure loss', function () {
    $c1 = makeCampaign($this->team->id, 'Wasted Budget');

    AdSpend::factory()->create([
        'project_id' => $this->pid,
        'stats_track_campaign_id' => $c1,
        'amount' => 3000,
        'starts_on' => '2026-06-01',
        'ends_on' => '2026-06-20',
    ]);

    $rows = $this->service->getCampaignProfitability(rangeFilter($this->pid, 30, '2026-06-21'));

    expect($rows)->toHaveCount(1);
    expect($rows[0])->toMatchArray([
        'campaign_id' => $c1,
        'net' => 0,
        'spend' => 3000,
        'net_profit' => -3000,
        'roas' => 0.0,
        'cac' => null,
    ]);
});

it('leaves roas null for revenue without recorded spend', function () {
    $c1 = makeCampaign($this->team->id, 'Organic-ish');
    makeCampaignUser('u1', $this->team->id, $this->pid, $c1, '2026-06-15 10:00:00');
    makeCampaignPayment('p1', 'u1', $this->team->id, $this->pid, 8000, '2026-06-15 10:00:00');

    $rows = $this->service->getCampaignProfitability(rangeFilter($this->pid, 30, '2026-06-21'));

    expect($rows[0]['roas'])->toBeNull()
        ->and($rows[0]['net_profit'])->toBe(8000);
});

it('sums multiple spend entries for the same campaign', function () {
    $c1 = makeCampaign($this->team->id, 'Meta Ads');
    makeCampaignUser('u1', $this->team->id, $this->pid, $c1, '2026-06-15 10:00:00');
    makeCampaignPayment('p1', 'u1', $this->team->id, $this->pid, 20000, '2026-06-15 10:00:00');

    AdSpend::factory()->create([
        'project_id' => $this->pid, 'stats_track_campaign_id' => $c1,
        'amount' => 4000, 'starts_on' => '2026-06-01', 'ends_on' => '2026-06-10',
    ]);
    AdSpend::factory()->create([
        'project_id' => $this->pid, 'stats_track_campaign_id' => $c1,
        'amount' => 6000, 'starts_on' => '2026-06-11', 'ends_on' => '2026-06-20',
    ]);

    $rows = $this->service->getCampaignProfitability(rangeFilter($this->pid, 30, '2026-06-21'));

    expect($rows[0]['spend'])->toBe(10000)
        ->and($rows[0]['roas'])->toBe(2.0);
});
