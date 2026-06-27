<?php

use App\DTOs\StatsFilterDto;
use App\Enums\StatsRangeType;
use App\Models\Project;
use App\Models\StatsPayment;
use App\Models\StatsUser;
use App\Models\StatsVisitor;
use App\Models\User;
use App\Services\StatsAggregationsService;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Database\Seeders\TestUserSeeder;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->seed(TestUserSeeder::class);
    $this->user = User::where('email', config('app.test.user')['email'])->firstOrFail();
    $this->project = Project::first();
    $this->team = $this->project->team;
    $this->pid = $this->project->id;

    // Clean slate so the seeded history does not pollute the cohort assertions.
    DB::table('stats_payments')->delete();
    DB::table('stats_logins')->delete();
    DB::table('stats_users')->delete();
    DB::table('stats_visitors')->delete();

    auth()->setUser($this->user);
    Context::add('team_id', $this->team->id);
    config(['app.stats_aggregation_enabled' => true]);

    Carbon::setTestNow('2026-06-26 12:00:00');
});

afterEach(function () {
    Carbon::setTestNow();
});

function cohortFebFilter(int $projectId): StatsFilterDto
{
    return new StatsFilterDto(
        projectId: $projectId,
        rangeAmount: 5,
        rangeType: StatsRangeType::MONTH,
        rangeStart: CarbonImmutable::parse('2026-06-26'),
        comparison: '0',
        cohortStart: CarbonImmutable::parse('2026-02-01 00:00:00'),
        cohortEnd: CarbonImmutable::parse('2026-03-01 00:00:00'),
    );
}

it('scopes registrations and revenue to the cohort registration window', function () {
    $cohortFeb = StatsUser::factory()->count(3)->create([
        'team_id' => $this->team->id,
        'project_id' => $this->pid,
        'time' => '2026-02-15 10:00:00',
        'time_utc' => '2026-02-15 10:00:00',
    ]);

    $cohortMar = StatsUser::factory()->count(2)->create([
        'team_id' => $this->team->id,
        'project_id' => $this->pid,
        'time' => '2026-03-15 10:00:00',
        'time_utc' => '2026-03-15 10:00:00',
    ]);

    // February cohort pays in April, i.e. after the registration window, so this
    // proves the scope follows the user's registration date, not the payment date.
    foreach ($cohortFeb as $user) {
        StatsPayment::factory()->create([
            'team_id' => $this->team->id,
            'project_id' => $this->pid,
            'stats_user_id' => $user->id,
            'gross' => 125,
            'net' => 100,
            'time' => '2026-04-10 10:00:00',
            'time_utc' => '2026-04-10 10:00:00',
        ]);
    }

    // March cohort revenue must be excluded by a February cohort filter.
    StatsPayment::factory()->create([
        'team_id' => $this->team->id,
        'project_id' => $this->pid,
        'stats_user_id' => $cohortMar->first()->id,
        'gross' => 1200,
        'net' => 999,
        'time' => '2026-04-10 10:00:00',
        'time_utc' => '2026-04-10 10:00:00',
    ]);

    $data = app(StatsAggregationsService::class)->getAggregatedData(cohortFebFilter($this->pid));

    $sum = fn (string $key) => collect($data)->sum($key);

    expect((int) $sum('pd_reg'))->toBe(3)
        ->and((int) round((float) $sum('pd_net')))->toBe(300);
});

it('treats visitor metrics as not applicable for a registration cohort', function () {
    StatsUser::factory()->count(3)->create([
        'team_id' => $this->team->id,
        'project_id' => $this->pid,
        'time' => '2026-02-15 10:00:00',
        'time_utc' => '2026-02-15 10:00:00',
    ]);

    StatsVisitor::factory()->count(6)->create([
        'team_id' => $this->team->id,
        'project_id' => $this->pid,
        'time' => '2026-02-12 10:00:00',
        'time_utc' => '2026-02-12 10:00:00',
    ]);

    $data = app(StatsAggregationsService::class)->getAggregatedData(cohortFebFilter($this->pid));

    expect((int) collect($data)->sum('pd_visitor'))->toBe(0);
});

it('produces a different cache signature per cohort window', function () {
    $feb = cohortFebFilter($this->pid);

    $mar = new StatsFilterDto(
        projectId: $this->pid,
        rangeAmount: 5,
        rangeType: StatsRangeType::MONTH,
        rangeStart: CarbonImmutable::parse('2026-06-26'),
        comparison: '0',
        cohortStart: CarbonImmutable::parse('2026-03-01 00:00:00'),
        cohortEnd: CarbonImmutable::parse('2026-04-01 00:00:00'),
    );

    expect($feb->makeHash())->not->toBe($mar->makeHash());
});

it('accepts a cohort filter on the dashboard and exposes it in the filters', function () {
    $this->actingAs($this->user)
        ->get(route('dashboard', [
            'project_id' => $this->pid,
            'cohort_start' => '2026-02-01 00:00:00',
            'cohort_end' => '2026-03-01 00:00:00',
            'preset' => 'custom',
            'range_type' => 'months',
            'range_amount' => 5,
            'range_start' => '2026-06-26',
        ]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('filters.cohort_start', '2026-02-01 00:00:00')
            ->where('filters.cohort_end', '2026-03-01 00:00:00')
        );
});

it('scopes to an arbitrary, non-bucket-aligned registration window', function () {
    StatsUser::factory()->count(2)->create([
        'team_id' => $this->team->id,
        'project_id' => $this->pid,
        'time' => '2026-02-15 10:00:00',
        'time_utc' => '2026-02-15 10:00:00',
    ]);

    StatsUser::factory()->count(3)->create([
        'team_id' => $this->team->id,
        'project_id' => $this->pid,
        'time' => '2026-02-02 10:00:00',
        'time_utc' => '2026-02-02 10:00:00',
    ]);

    $filters = new StatsFilterDto(
        projectId: $this->pid,
        rangeAmount: 5,
        rangeType: StatsRangeType::MONTH,
        rangeStart: CarbonImmutable::parse('2026-06-26'),
        comparison: '0',
        cohortStart: CarbonImmutable::parse('2026-02-14 00:00:00'),
        cohortEnd: CarbonImmutable::parse('2026-02-16 00:00:00'),
    );

    $data = app(StatsAggregationsService::class)->getAggregatedData($filters);

    expect((int) collect($data)->sum('pd_reg'))->toBe(2);
});

it('accepts a picker-style cohort with a custom observation period', function () {
    $this->actingAs($this->user)
        ->get(route('dashboard', [
            'project_id' => $this->pid,
            'cohort_start' => '2026-02-14 00:00:00',
            'cohort_end' => '2026-02-16 00:00:00',
            'cohort_label' => 'Feb 14 – Feb 15, 2026',
            'preset' => 'custom',
            'range_type' => 'months',
            'range_amount' => 5,
            'range_start' => '2026-06-26',
        ]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('filters.cohort_label', 'Feb 14 – Feb 15, 2026'));
});

it('carries the cohort display label through to the filters', function () {
    $this->actingAs($this->user)
        ->get(route('dashboard', [
            'project_id' => $this->pid,
            'cohort_start' => '2026-02-01 00:00:00',
            'cohort_end' => '2026-03-01 00:00:00',
            'cohort_label' => '2026-06',
            'preset' => 'custom',
            'range_type' => 'weeks',
            'range_amount' => 5,
            'range_start' => '2026-06-26',
        ]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('filters.cohort_label', '2026-06'));
});

it('clears the cohort filter when the window is submitted empty', function () {
    $this->actingAs($this->user)
        ->get(route('dashboard', [
            'project_id' => $this->pid,
            'cohort_start' => '',
            'cohort_end' => '',
            'cohort_label' => '',
        ]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->missing('filters.cohort_start')
            ->missing('filters.cohort_end')
        );
});

it('accepts a cohort filter on the recurring revenue page', function () {
    $this->actingAs($this->user)
        ->get(route('revenue', [
            'project_id' => $this->pid,
            'cohort_start' => '2026-02-01 00:00:00',
            'cohort_end' => '2026-03-01 00:00:00',
            'preset' => 'custom',
            'range_type' => 'months',
            'range_amount' => 5,
            'range_start' => '2026-06-26',
        ]))
        ->assertOk();
});
