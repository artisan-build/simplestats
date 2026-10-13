<?php

use App\Mcp\Servers\SimpleStatsServer;
use App\Mcp\Tools\GetGroupedBreakdown;
use App\Mcp\Tools\GetKpiStats;
use App\Mcp\Tools\ListStatsCapabilities;
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
    DB::table('stats_visitors')->delete();
    DB::table('stats_users')->delete();

    auth()->setUser($this->user);
    Context::add('team_id', $this->team->id);
});

function aiToolInsertVisitors(int $teamId, int $projectId, string $time, int $count, ?int $refererId = null): void
{
    for ($i = 0; $i < $count; $i++) {
        DB::table('stats_visitors')->insert([
            'team_id' => $teamId,
            'project_id' => $projectId,
            'visitor_hash' => Str::uuid()->toString(),
            'stats_track_referer_id' => $refererId,
            'time' => $time,
            'time_utc' => $time,
        ]);
    }
}

function aiToolInsertUserWithPayment(int $teamId, int $projectId, int $refererId, string $time, float $net): string
{
    $userId = (string) Str::uuid();

    DB::table('stats_users')->insert([
        'id' => $userId,
        'team_id' => $teamId,
        'project_id' => $projectId,
        'stats_track_referer_id' => $refererId,
        'time' => $time,
        'time_utc' => $time,
        'registered' => true,
    ]);

    DB::table('stats_payments')->insert([
        'id' => (string) Str::uuid(),
        'stats_user_id' => $userId,
        'team_id' => $teamId,
        'project_id' => $projectId,
        'gross' => $net * 1.2,
        'net' => $net,
        'currency' => 'EUR',
        'time' => $time,
        'time_utc' => $time,
        'counter' => 1,
    ]);

    return $userId;
}

function aiToolMakeReferer(int $teamId, string $name): int
{
    return DB::table('stats_track_referers')->insertGetId([
        'team_id' => $teamId,
        'name' => $name,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

it('lists capabilities with projects, dimensions, presets and kpis', function (): void {
    SimpleStatsServer::actingAs($this->user)
        ->tool(ListStatsCapabilities::class)
        ->assertOk()
        ->assertSee('All projects')
        ->assertSee($this->project->name)
        ->assertSee('track_referer')
        ->assertSee('last_month')
        ->assertSee('pd_reg');
});

it('returns kpi stats with totals for a preset range', function (): void {
    $time = now()->subDay()->format('Y-m-d').' 12:00:00';
    aiToolInsertVisitors($this->team->id, $this->project->id, $time, 5);

    SimpleStatsServer::actingAs($this->user)
        ->tool(GetKpiStats::class, ['preset' => 'last_7_days', 'project_id' => $this->project->id])
        ->assertOk()
        ->assertStructuredContent(function ($json): void {
            $json->where('totals.pd_visitor', fn ($count): bool => (float) $count === 5.0)
                ->count('rows', 7)
                ->etc();
        });
});

it('adds per-project totals when the scope is all projects', function (): void {
    $secondProject = Project::factory()
        ->state(['name' => 'Second App', 'usage' => $this->project->usage])
        ->for($this->team)
        ->create();

    $time = now()->subDay()->format('Y-m-d').' 12:00:00';
    aiToolInsertVisitors($this->team->id, $this->project->id, $time, 5);
    aiToolInsertVisitors($this->team->id, $secondProject->id, $time, 3);

    SimpleStatsServer::actingAs($this->user)
        ->tool(GetKpiStats::class, ['preset' => 'last_7_days', 'project_id' => 0])
        ->assertOk()
        ->assertStructuredContent(function ($json): void {
            $json->where('totals.pd_visitor', fn ($count): bool => (float) $count === 8.0)
                ->where('per_project.0.project', $this->project->name)
                ->where('per_project.0.totals.pd_visitor', fn ($count): bool => (float) $count === 5.0)
                ->where('per_project.1.project', 'Second App')
                ->where('per_project.1.totals.pd_visitor', fn ($count): bool => (float) $count === 3.0)
                ->etc();
        });
});

it('omits per-project totals for a single project scope', function (): void {
    $time = now()->subDay()->format('Y-m-d').' 12:00:00';
    aiToolInsertVisitors($this->team->id, $this->project->id, $time, 5);

    SimpleStatsServer::actingAs($this->user)
        ->tool(GetKpiStats::class, ['preset' => 'last_7_days', 'project_id' => $this->project->id])
        ->assertOk()
        ->assertStructuredContent(function ($json): void {
            $json->missing('per_project')->etc();
        });
});

it('rejects a call without any date range', function (): void {
    SimpleStatsServer::actingAs($this->user)
        ->tool(GetKpiStats::class, [])
        ->assertHasErrors();
});

it('breaks down revenue by referrer', function (): void {
    $time = now()->subDay()->format('Y-m-d').' 12:00:00';

    $googleId = aiToolMakeReferer($this->team->id, 'google.com');
    $duckId = aiToolMakeReferer($this->team->id, 'duckduckgo.com');

    // Payment amounts are stored in cents: 10000 = 100.00 in team currency.
    aiToolInsertUserWithPayment($this->team->id, $this->project->id, $googleId, $time, 10000.0);
    aiToolInsertUserWithPayment($this->team->id, $this->project->id, $duckId, $time, 2500.0);

    SimpleStatsServer::actingAs($this->user)
        ->tool(GetGroupedBreakdown::class, [
            'dimension' => 'track_referer',
            'sort' => 'net',
            'preset' => 'last_7_days',
            'project_id' => $this->project->id,
        ])
        ->assertOk()
        ->assertSee('google.com')
        ->assertSee('duckduckgo.com')
        ->assertStructuredContent(function ($json): void {
            $json->where('rows.0.name', 'google.com')
                ->where('rows.0.net', fn ($net): bool => (float) $net === 100.0)
                ->where('scope.currency', 'EUR')
                ->etc();
        });
});

it('rejects an unknown dimension', function (): void {
    SimpleStatsServer::actingAs($this->user)
        ->tool(GetGroupedBreakdown::class, [
            'dimension' => 'not_a_dimension',
            'preset' => 'last_7_days',
        ])
        ->assertHasErrors();
});

it('scopes stats to the acting user\'s team', function (): void {
    $time = now()->subDay()->format('Y-m-d').' 12:00:00';
    aiToolInsertVisitors($this->team->id, $this->project->id, $time, 5);

    $otherUser = User::factory()->withPersonalTeam()->create();

    auth()->forgetGuards();
    Context::flush();
    auth()->setUser($otherUser);
    Context::add('team_id', $otherUser->personalTeam()->id);

    SimpleStatsServer::actingAs($otherUser)
        ->tool(GetKpiStats::class, ['preset' => 'last_7_days'])
        ->assertOk()
        ->assertStructuredContent(function ($json): void {
            $json->where('totals.pd_visitor', fn ($count): bool => (float) $count === 0.0)->etc();
        });
});
