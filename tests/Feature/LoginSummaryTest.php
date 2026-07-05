<?php

use App\Models\Project;
use App\Models\StatsPayment;
use App\Models\StatsUser;
use App\Models\StatsVisitor;
use App\Models\User;
use App\Services\LoginSummaryService;

beforeEach(function () {
    $this->user = User::factory()->withPersonalTeam()->create();
    $this->user->currentTeam->update(['currency' => 'USD', 'timezone' => 'UTC']);
    $this->project = Project::factory()->create(['team_id' => $this->user->currentTeam->id]);
    $this->actingAs($this->user);
});

test('login captures previous login and marks the summary pending', function () {
    $oldLogin = now()->subDays(3)->startOfSecond();
    $this->user->update(['last_login_at' => $oldLogin]);

    $this->post(route('logout'));

    $this->post(route('login'), [
        'email' => $this->user->email,
        'password' => 'password',
    ]);

    $this->user->refresh();

    expect($this->user->previous_login_at->format('Y-m-d H:i:s'))->toBe($oldLogin->format('Y-m-d H:i:s'));
    expect($this->user->last_login_at)->not->toBe($oldLogin->format('Y-m-d H:i:s'));
    expect(session()->has(LoginSummaryService::SESSION_KEY))->toBeTrue();
});

test('summary is eligible after a login when the previous login is old enough', function () {
    $this->user->update(['previous_login_at' => now()->subHours(13)]);

    $this->withSession([LoginSummaryService::SESSION_KEY => true])
        ->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page->where('loginSummaryEligible', true));
});

test('summary is not eligible when the previous login is too recent', function () {
    $this->user->update(['previous_login_at' => now()->subHours(2)]);

    $this->withSession([LoginSummaryService::SESSION_KEY => true])
        ->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page->where('loginSummaryEligible', false));
});

test('summary is not eligible without a fresh login event', function () {
    $this->user->update(['previous_login_at' => now()->subHours(13)]);

    $this->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page->where('loginSummaryEligible', false));
});

test('summary is not eligible when disabled in the user settings', function () {
    $this->user->update(['previous_login_at' => now()->subHours(13)]);
    $this->user->updateSettings('loginSummaryEnabled', false);

    $this->withSession([LoginSummaryService::SESSION_KEY => true])
        ->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page->where('loginSummaryEligible', false));
});

test('summary is not eligible when there is no previous login', function () {
    $this->user->update(['previous_login_at' => null]);

    $this->withSession([LoginSummaryService::SESSION_KEY => true])
        ->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page->where('loginSummaryEligible', false));
});

test('summary endpoint returns the stats since the previous login', function () {
    $this->user->update(['previous_login_at' => now()->subHours(24)]);

    StatsVisitor::factory()->create([
        'team_id' => $this->user->currentTeam->id,
        'project_id' => $this->project->id,
        'time' => now()->subHours(3)->format('Y-m-d H:i:s'),
    ]);

    $statsUser = StatsUser::factory()->create([
        'team_id' => $this->user->currentTeam->id,
        'project_id' => $this->project->id,
        'time' => now()->subHours(2)->format('Y-m-d H:i:s'),
    ]);

    StatsPayment::factory()->create([
        'team_id' => $this->user->currentTeam->id,
        'project_id' => $this->project->id,
        'stats_user_id' => $statsUser->id,
        'gross' => 1000,
        'net' => 800,
        'time' => now()->subHour()->format('Y-m-d H:i:s'),
    ]);

    $this->withSession([LoginSummaryService::SESSION_KEY => true])
        ->getJson(route('login-summary'))
        ->assertSuccessful()
        ->assertJsonPath('summary.metrics.visitors', 1)
        ->assertJsonPath('summary.metrics.reg', 1)
        ->assertJsonPath('summary.metrics.net', 800);
});

test('summary endpoint ignores stats from before the previous login', function () {
    $this->user->update(['previous_login_at' => now()->subHours(24)]);

    StatsVisitor::factory()->create([
        'team_id' => $this->user->currentTeam->id,
        'project_id' => $this->project->id,
        'time' => now()->subDays(3)->format('Y-m-d H:i:s'),
    ]);

    $this->withSession([LoginSummaryService::SESSION_KEY => true])
        ->getJson(route('login-summary'))
        ->assertSuccessful()
        ->assertJsonPath('summary', null);
});

test('summary is null when there is nothing to celebrate and the pending flag is still consumed', function () {
    $this->user->update(['previous_login_at' => now()->subHours(24)]);

    $this->withSession([LoginSummaryService::SESSION_KEY => true])
        ->getJson(route('login-summary'))
        ->assertSuccessful()
        ->assertJsonPath('summary', null);

    $this->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page->where('loginSummaryEligible', false));
});

test('summary can only be fetched once per login', function () {
    $this->user->update(['previous_login_at' => now()->subHours(24)]);

    StatsVisitor::factory()->create([
        'team_id' => $this->user->currentTeam->id,
        'project_id' => $this->project->id,
        'time' => now()->subHour()->format('Y-m-d H:i:s'),
    ]);

    $this->withSession([LoginSummaryService::SESSION_KEY => true])
        ->getJson(route('login-summary'))
        ->assertJsonPath('summary.metrics.visitors', 1);

    // new stats arriving mid-session must not retrigger the modal
    StatsVisitor::factory()->create([
        'team_id' => $this->user->currentTeam->id,
        'project_id' => $this->project->id,
        'time' => now()->format('Y-m-d H:i:s'),
    ]);

    $this->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page->where('loginSummaryEligible', false));

    $this->getJson(route('login-summary'))
        ->assertJsonPath('summary', null);
});

test('summary only contains metrics the team tracks', function () {
    $this->project->update(['usage' => Project::USAGE_VISITORS]);
    $this->user->update(['previous_login_at' => now()->subHours(24)]);

    StatsVisitor::factory()->create([
        'team_id' => $this->user->currentTeam->id,
        'project_id' => $this->project->id,
        'time' => now()->subHour()->format('Y-m-d H:i:s'),
    ]);

    $response = $this->withSession([LoginSummaryService::SESSION_KEY => true])
        ->getJson(route('login-summary'))
        ->assertSuccessful();

    expect($response->json('summary.metrics'))->toBe(['visitors' => 1]);
});

test('the login summary setting can be toggled', function () {
    $this->post(route('user-settings.login-summary'), ['enabled' => false])
        ->assertRedirect();

    expect($this->user->refresh()->settings['loginSummaryEnabled'])->toBeFalse();

    $this->post(route('user-settings.login-summary'), ['enabled' => true]);

    expect($this->user->refresh()->settings['loginSummaryEnabled'])->toBeTrue();
});

test('the login summary setting requires a boolean', function () {
    $this->post(route('user-settings.login-summary'), ['enabled' => 'nope'])
        ->assertSessionHasErrors('enabled');
});

test('the notifications page exposes the login summary setting', function () {
    $this->get(route('notifications.index'))
        ->assertInertia(fn ($page) => $page->where('loginSummaryEnabled', true));
});
