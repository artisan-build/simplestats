<?php

use App\Mcp\Servers\SimpleStatsServer;
use App\Mcp\Tools\GetRecurringRevenueStats;
use App\Mcp\Tools\GetRetentionCohorts;
use App\Models\Project;
use App\Models\User;
use Database\Seeders\TestUserSeeder;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

beforeEach(function (): void {
    $this->seed(TestUserSeeder::class);
    $this->user = User::where('email', config('app.test.user')['email'])->firstOrFail();
    $this->project = Project::first();
    $this->team = $this->project->team;

    DB::table('stats_payments')->delete();
    DB::table('stats_logins')->delete();
    DB::table('stats_users')->delete();

    auth()->setUser($this->user);
    Context::add('team_id', $this->team->id);
});

function revToolInsertUser(string $id, int $teamId, int $projectId, string $time): void
{
    DB::table('stats_users')->insert([
        'id' => $id,
        'team_id' => $teamId,
        'project_id' => $projectId,
        'time' => $time,
        'time_utc' => $time,
        'registered' => true,
    ]);
}

function revToolInsertSubscriptionPayment(string $userId, int $teamId, int $projectId, string $time, float $net): void
{
    DB::table('stats_payments')->insert([
        'id' => (string) Str::uuid(),
        'stats_user_id' => $userId,
        'team_id' => $teamId,
        'project_id' => $projectId,
        'gross' => $net,
        'net' => $net,
        'currency' => 'EUR',
        'subscription_interval' => 'month',
        'time' => $time,
        'time_utc' => $time,
        'counter' => 1,
    ]);
}

it('returns the mrr snapshot with money converted from cents', function (): void {
    $this->project->update(['usage' => $this->project->usage | Project::USAGE_SUBSCRIPTION]);

    $time = now()->subDay()->format('Y-m-d').' 12:00:00';
    revToolInsertUser('sub-user', $this->team->id, $this->project->id, $time);
    // 10000 cents = 100.00 in team currency.
    revToolInsertSubscriptionPayment('sub-user', $this->team->id, $this->project->id, $time, 10000.0);

    SimpleStatsServer::actingAs($this->user)
        ->tool(GetRecurringRevenueStats::class, [
            'section' => 'snapshot',
            'preset' => 'last_7_days',
            'project_id' => $this->project->id,
        ])
        ->assertOk()
        ->assertStructuredContent(function ($json): void {
            $json->where('section', 'snapshot')
                ->where('data.mrr', fn ($mrr): bool => (float) $mrr === 100.0)
                ->where('data.arr', fn ($arr): bool => (float) $arr === 1200.0)
                ->etc();
        });
});

it('adds per-project mrr snapshots when the scope is all projects', function (): void {
    $this->project->update(['usage' => $this->project->usage | Project::USAGE_SUBSCRIPTION]);

    $secondProject = Project::factory()
        ->state(['name' => 'Second App', 'usage' => $this->project->usage])
        ->for($this->team)
        ->create();

    $time = now()->subDay()->format('Y-m-d').' 12:00:00';
    revToolInsertUser('sub-a', $this->team->id, $this->project->id, $time);
    revToolInsertSubscriptionPayment('sub-a', $this->team->id, $this->project->id, $time, 10000.0);
    revToolInsertUser('sub-b', $this->team->id, $secondProject->id, $time);
    revToolInsertSubscriptionPayment('sub-b', $this->team->id, $secondProject->id, $time, 5000.0);

    SimpleStatsServer::actingAs($this->user)
        ->tool(GetRecurringRevenueStats::class, [
            'section' => 'snapshot',
            'preset' => 'last_7_days',
            'project_id' => 0,
        ])
        ->assertOk()
        ->assertStructuredContent(function ($json): void {
            $json->where('data.mrr', fn ($mrr): bool => (float) $mrr === 150.0)
                ->where('per_project.0.project', $this->project->name)
                ->where('per_project.0.mrr', fn ($mrr): bool => (float) $mrr === 100.0)
                ->where('per_project.1.project', 'Second App')
                ->where('per_project.1.mrr', fn ($mrr): bool => (float) $mrr === 50.0)
                ->etc();
        });
});

it('notes when the project does not track subscriptions', function (): void {
    SimpleStatsServer::actingAs($this->user)
        ->tool(GetRecurringRevenueStats::class, [
            'section' => 'snapshot',
            'preset' => 'last_7_days',
            'project_id' => $this->project->id,
        ])
        ->assertOk()
        ->assertSee('does not track subscriptions');
});

it('rejects an unknown section', function (): void {
    SimpleStatsServer::actingAs($this->user)
        ->tool(GetRecurringRevenueStats::class, [
            'section' => 'not_a_section',
            'preset' => 'last_7_days',
        ])
        ->assertHasErrors();
});

it('returns retention cohorts with the baseline bucket', function (): void {
    $time = now()->subDay()->format('Y-m-d').' 12:00:00';
    revToolInsertUser('cohort-user', $this->team->id, $this->project->id, $time);

    SimpleStatsServer::actingAs($this->user)
        ->tool(GetRetentionCohorts::class, [
            'preset' => 'last_7_days',
            'project_id' => $this->project->id,
        ])
        ->assertOk()
        ->assertStructuredContent(function ($json): void {
            $json->where('data.period_count', 7)
                ->has('data.cohorts')
                ->etc();
        });
});

it('notes when the project does not track registrations', function (): void {
    $this->project->update(['usage' => Project::USAGE_VISITORS]);

    SimpleStatsServer::actingAs($this->user)
        ->tool(GetRetentionCohorts::class, [
            'preset' => 'last_7_days',
            'project_id' => $this->project->id,
        ])
        ->assertOk()
        ->assertSee('does not track registrations');
});
