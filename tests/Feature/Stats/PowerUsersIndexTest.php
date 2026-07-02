<?php

use App\Models\Project;
use App\Models\StatsLogin;
use App\Models\StatsPayment;
use App\Models\StatsUser;
use App\Models\User;
use Carbon\CarbonImmutable;

beforeEach(function () {
    CarbonImmutable::setTestNow('2026-06-21 12:00:00');

    $this->user = User::factory()->withPersonalTeam()->create();
    $this->team = $this->user->currentTeam;
    $this->team->update(['currency' => 'EUR', 'timezone' => 'UTC']);
    $this->project = Project::factory()->create([
        'team_id' => $this->team->id,
        'usage' => Project::USAGE_VISITORS | Project::USAGE_USERS | Project::USAGE_PAYMENTS,
    ]);
    $this->actingAs($this->user);
});

afterEach(function () {
    CarbonImmutable::setTestNow();
});

function makePowerUser(string $id, Project $project, array $attributes = []): StatsUser
{
    return StatsUser::factory()->create(array_merge([
        'id' => $id,
        'team_id' => $project->team_id,
        'project_id' => $project->id,
    ], $attributes));
}

test('power-users route returns 200 and renders the page with shared props', function () {
    $this->get(route('power-users'))
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->component('PowerUsers/Index')
            ->has('teamStats')
            ->has('filters')
            ->has('usageBits')
            ->has('powerUsers')
            ->has('permissions')
            ->where('sort.column', null)
            ->where('sort.direction', 'desc')
        );
});

test('a whitelisted sort column reorders the power users and is echoed back', function () {
    $whale = makePowerUser('whale', $this->project);
    $active = makePowerUser('active', $this->project);

    StatsPayment::factory()->create(['id' => 'p-whale', 'stats_user_id' => 'whale', 'team_id' => $this->team->id, 'project_id' => $this->project->id, 'gross' => 5000, 'net' => 4000]);
    StatsPayment::factory()->create(['id' => 'p-active', 'stats_user_id' => 'active', 'team_id' => $this->team->id, 'project_id' => $this->project->id, 'gross' => 1000, 'net' => 800]);

    $this->get(route('power-users', ['project_id' => $this->project->id, 'sort' => 'revenue', 'direction' => 'asc']))
        ->assertInertia(fn ($page) => $page
            ->where('sort.column', 'revenue')
            ->where('sort.direction', 'asc')
            ->where('powerUsers.data.0.id', 'active')
            ->where('powerUsers.data.1.id', 'whale')
        );
});

test('sorting by last login orders by recency and sinks users without a login', function () {
    makePowerUser('recent', $this->project);
    makePowerUser('older', $this->project);
    makePowerUser('never', $this->project);

    StatsLogin::factory()->create(['stats_user_id' => 'recent', 'team_id' => $this->team->id, 'project_id' => $this->project->id, 'time' => '2026-06-15 10:00:00']);
    StatsLogin::factory()->create(['stats_user_id' => 'older', 'team_id' => $this->team->id, 'project_id' => $this->project->id, 'time' => '2026-06-01 10:00:00']);

    $this->get(route('power-users', ['project_id' => $this->project->id, 'sort' => 'last_login', 'direction' => 'desc']))
        ->assertInertia(fn ($page) => $page
            ->where('sort.column', 'last_login')
            ->where('powerUsers.data.0.id', 'recent')
            ->where('powerUsers.data.1.id', 'older')
            ->where('powerUsers.data.2.id', 'never')
        );
});

test('power users can be sorted alphabetically by their current plan', function () {
    $this->project->update(['usage' => Project::USAGE_VISITORS | Project::USAGE_USERS | Project::USAGE_PAYMENTS | Project::USAGE_SUBSCRIPTION]);

    makePowerUser('starter-user', $this->project);
    makePowerUser('pro-user', $this->project);

    StatsPayment::factory()->subscriptionMonthly()->create(['id' => 's1', 'stats_user_id' => 'starter-user', 'team_id' => $this->team->id, 'project_id' => $this->project->id, 'gross' => 1000, 'net' => 800, 'subscription_plan' => 'Starter', 'time' => '2026-06-01 10:00:00']);
    StatsPayment::factory()->subscriptionMonthly()->create(['id' => 's2', 'stats_user_id' => 'pro-user', 'team_id' => $this->team->id, 'project_id' => $this->project->id, 'gross' => 2000, 'net' => 1600, 'subscription_plan' => 'Pro', 'time' => '2026-06-01 10:00:00']);

    $this->get(route('power-users', ['project_id' => $this->project->id, 'sort' => 'plan', 'direction' => 'asc']))
        ->assertInertia(fn ($page) => $page
            ->where('sort.column', 'plan')
            ->where('powerUsers.data.0.current_plan', 'Pro')
            ->where('powerUsers.data.1.current_plan', 'Starter')
        );
});

test('an unknown sort column falls back to the default lifetime-value ranking', function () {
    $whale = makePowerUser('whale', $this->project);
    $active = makePowerUser('active', $this->project);

    StatsPayment::factory()->create(['id' => 'p-whale', 'stats_user_id' => 'whale', 'team_id' => $this->team->id, 'project_id' => $this->project->id, 'gross' => 5000, 'net' => 4000]);
    StatsPayment::factory()->create(['id' => 'p-active', 'stats_user_id' => 'active', 'team_id' => $this->team->id, 'project_id' => $this->project->id, 'gross' => 1000, 'net' => 800]);

    $this->get(route('power-users', ['project_id' => $this->project->id, 'sort' => 'id; DROP TABLE users']))
        ->assertInertia(fn ($page) => $page
            ->where('sort.column', null)
            ->where('powerUsers.data.0.id', 'whale')
            ->where('powerUsers.data.1.id', 'active')
        );
});

test('power-users redirects to dashboard when the team does not track registrations', function () {
    $this->project->update(['usage' => Project::USAGE_VISITORS]);

    $this->get(route('power-users'))->assertRedirect(route('dashboard'));
});

test('power users are ranked by total gross revenue, then by login count', function () {
    $whale = makePowerUser('whale', $this->project);
    $active = makePowerUser('active', $this->project);
    $quiet = makePowerUser('quiet', $this->project);

    StatsPayment::factory()->create(['id' => 'p-whale', 'stats_user_id' => 'whale', 'team_id' => $this->team->id, 'project_id' => $this->project->id, 'gross' => 5000, 'net' => 4000]);
    StatsPayment::factory()->create(['id' => 'p-active', 'stats_user_id' => 'active', 'team_id' => $this->team->id, 'project_id' => $this->project->id, 'gross' => 1000, 'net' => 800]);
    // quiet has no payment (gross 0)

    // active and quiet tie on gross with each other only vs no-login ordering; give active more logins than quiet
    StatsLogin::factory()->count(3)->create(['stats_user_id' => 'active', 'team_id' => $this->team->id, 'project_id' => $this->project->id]);
    StatsLogin::factory()->count(1)->create(['stats_user_id' => 'quiet', 'team_id' => $this->team->id, 'project_id' => $this->project->id]);

    $this->get(route('power-users'))
        ->assertInertia(fn ($page) => $page
            ->has('powerUsers.data', 3)
            ->where('powerUsers.data.0.id', 'whale')
            ->where('powerUsers.data.1.id', 'active')
            ->where('powerUsers.data.2.id', 'quiet')
            ->where('powerUsers.data.0.total_gross', 5000)
        );
});

test('power users aggregate login count, last login and total gross per user', function () {
    makePowerUser('u1', $this->project);

    StatsLogin::factory()->create(['stats_user_id' => 'u1', 'team_id' => $this->team->id, 'project_id' => $this->project->id, 'time' => '2026-06-01 08:00:00']);
    StatsLogin::factory()->create(['stats_user_id' => 'u1', 'team_id' => $this->team->id, 'project_id' => $this->project->id, 'time' => '2026-06-10 09:30:00']);

    StatsPayment::factory()->create(['id' => 'pa', 'stats_user_id' => 'u1', 'team_id' => $this->team->id, 'project_id' => $this->project->id, 'gross' => 1500, 'net' => 1200]);
    StatsPayment::factory()->create(['id' => 'pb', 'stats_user_id' => 'u1', 'team_id' => $this->team->id, 'project_id' => $this->project->id, 'gross' => 500, 'net' => 400]);

    $this->get(route('power-users'))
        ->assertInertia(fn ($page) => $page
            ->has('powerUsers.data', 1, fn ($row) => $row
                ->where('id', 'u1')
                ->where('logins_count', 2)
                ->where('total_gross', 2000)
                ->where('last_login_at', fn ($value) => str_contains($value, '2026-06-10'))
                ->etc()
            )
        );
});

test('aggregates do not leak across projects that share the same external user id', function () {
    $other = Project::factory()->create([
        'team_id' => $this->team->id,
        'usage' => Project::USAGE_VISITORS | Project::USAGE_USERS | Project::USAGE_PAYMENTS,
    ]);

    makePowerUser('shared', $this->project);
    makePowerUser('shared', $other);

    StatsPayment::factory()->create(['id' => 'x1', 'stats_user_id' => 'shared', 'team_id' => $this->team->id, 'project_id' => $this->project->id, 'gross' => 100, 'net' => 80]);
    StatsPayment::factory()->create(['id' => 'x2', 'stats_user_id' => 'shared', 'team_id' => $this->team->id, 'project_id' => $other->id, 'gross' => 900, 'net' => 720]);

    $this->get(route('power-users', ['project_id' => $this->project->id]))
        ->assertInertia(fn ($page) => $page
            ->has('powerUsers.data', 1, fn ($row) => $row
                ->where('id', 'shared')
                ->where('total_gross', 100)
                ->etc()
            )
        );
});

test('selecting all projects shows users team-wide, a specific project filters them', function () {
    $other = Project::factory()->create([
        'team_id' => $this->team->id,
        'usage' => Project::USAGE_VISITORS | Project::USAGE_USERS | Project::USAGE_PAYMENTS,
    ]);

    makePowerUser('a', $this->project);
    makePowerUser('b', $other);

    $this->get(route('power-users', ['project_id' => 0]))
        ->assertInertia(fn ($page) => $page->has('powerUsers.data', 2));

    $this->get(route('power-users', ['project_id' => $this->project->id]))
        ->assertInertia(fn ($page) => $page
            ->has('powerUsers.data', 1)
            ->where('powerUsers.data.0.id', 'a')
        );
});

test('current plan is the latest subscription payment plan when subscriptions are tracked', function () {
    $this->project->update(['usage' => Project::USAGE_VISITORS | Project::USAGE_USERS | Project::USAGE_PAYMENTS | Project::USAGE_SUBSCRIPTION]);

    makePowerUser('sub', $this->project);

    StatsPayment::factory()->subscriptionMonthly()->create(['id' => 's1', 'stats_user_id' => 'sub', 'team_id' => $this->team->id, 'project_id' => $this->project->id, 'gross' => 1000, 'net' => 800, 'subscription_plan' => 'Starter', 'time' => '2026-05-01 10:00:00']);
    StatsPayment::factory()->subscriptionMonthly()->create(['id' => 's2', 'stats_user_id' => 'sub', 'team_id' => $this->team->id, 'project_id' => $this->project->id, 'gross' => 2000, 'net' => 1600, 'subscription_plan' => 'Pro', 'time' => '2026-06-01 10:00:00']);

    $this->get(route('power-users', ['project_id' => $this->project->id]))
        ->assertInertia(fn ($page) => $page
            ->has('powerUsers.data', 1, fn ($row) => $row
                ->where('id', 'sub')
                ->where('current_plan', 'Pro')
                ->etc()
            )
        );
});

test('a churned monthly subscriber whose last payment is older than the interval has no current plan', function () {
    $this->project->update(['usage' => Project::USAGE_VISITORS | Project::USAGE_USERS | Project::USAGE_PAYMENTS | Project::USAGE_SUBSCRIPTION]);

    makePowerUser('churned', $this->project);

    // Test-now is 2026-06-21; the month window (interval + 3 grace days) starts 2026-05-18.
    StatsPayment::factory()->subscriptionMonthly()->create(['id' => 'c1', 'stats_user_id' => 'churned', 'team_id' => $this->team->id, 'project_id' => $this->project->id, 'gross' => 1000, 'net' => 800, 'subscription_plan' => 'Starter', 'time' => '2026-05-01 10:00:00']);

    $this->get(route('power-users', ['project_id' => $this->project->id]))
        ->assertInertia(fn ($page) => $page
            ->has('powerUsers.data', 1, fn ($row) => $row
                ->where('id', 'churned')
                ->where('current_plan', null)
                ->etc()
            )
        );
});

test('a yearly subscriber stays on their plan even when the last payment is months ago', function () {
    $this->project->update(['usage' => Project::USAGE_VISITORS | Project::USAGE_USERS | Project::USAGE_PAYMENTS | Project::USAGE_SUBSCRIPTION]);

    makePowerUser('yearly', $this->project);

    StatsPayment::factory()->subscriptionYearly()->create(['id' => 'y1', 'stats_user_id' => 'yearly', 'team_id' => $this->team->id, 'project_id' => $this->project->id, 'gross' => 12000, 'net' => 9600, 'subscription_plan' => 'Pro Yearly', 'time' => '2025-12-15 10:00:00']);

    $this->get(route('power-users', ['project_id' => $this->project->id]))
        ->assertInertia(fn ($page) => $page
            ->has('powerUsers.data', 1, fn ($row) => $row
                ->where('id', 'yearly')
                ->where('current_plan', 'Pro Yearly')
                ->etc()
            )
        );
});

test('only the latest recurring payment decides activity, an older in-window yearly does not resurrect a churned monthly', function () {
    $this->project->update(['usage' => Project::USAGE_VISITORS | Project::USAGE_USERS | Project::USAGE_PAYMENTS | Project::USAGE_SUBSCRIPTION]);

    makePowerUser('switcher', $this->project);

    // Yearly payment still inside the year window, but the user later switched to
    // monthly and let that lapse: the latest recurring payment rules, so no plan.
    StatsPayment::factory()->subscriptionYearly()->create(['id' => 'sw1', 'stats_user_id' => 'switcher', 'team_id' => $this->team->id, 'project_id' => $this->project->id, 'gross' => 12000, 'net' => 9600, 'subscription_plan' => 'Pro Yearly', 'time' => '2025-10-01 10:00:00']);
    StatsPayment::factory()->subscriptionMonthly()->create(['id' => 'sw2', 'stats_user_id' => 'switcher', 'team_id' => $this->team->id, 'project_id' => $this->project->id, 'gross' => 1000, 'net' => 800, 'subscription_plan' => 'Starter', 'time' => '2026-04-01 10:00:00']);

    $this->get(route('power-users', ['project_id' => $this->project->id]))
        ->assertInertia(fn ($page) => $page
            ->has('powerUsers.data', 1, fn ($row) => $row
                ->where('id', 'switcher')
                ->where('current_plan', null)
                ->etc()
            )
        );
});

test('each row exposes the location, device and referer dimensions for icons', function () {
    makePowerUser('dim', $this->project, [
        'location_country' => 'Germany (DE)',
        'device_type' => 'Desktop',
        'device_platform' => 'Mac',
        'device_browser' => 'Chrome',
        'track_referer' => 'google.com',
    ]);

    $this->get(route('power-users', ['project_id' => $this->project->id]))
        ->assertInertia(fn ($page) => $page
            ->has('powerUsers.data', 1, fn ($row) => $row
                ->where('id', 'dim')
                ->where('stats_location_country.name', 'Germany (DE)')
                ->where('stats_device_type.name', 'Desktop')
                ->where('stats_device_platform.name', 'Mac')
                ->where('stats_device_browser.name', 'Chrome')
                ->where('stats_track_referer.name', 'google.com')
                ->etc()
            )
        );
});

test('the selected project impersonate base url is exposed on each row for building links', function () {
    $this->project->update(['impersonate_base_url' => 'https://app.example.com/impersonate/take']);

    makePowerUser('imp', $this->project);

    $this->get(route('power-users', ['project_id' => $this->project->id]))
        ->assertInertia(fn ($page) => $page
            ->has('powerUsers.data', 1, fn ($row) => $row
                ->where('id', 'imp')
                ->where('project.impersonate_base_url', 'https://app.example.com/impersonate/take')
                ->etc()
            )
        );
});

test('updating the impersonate base url stores it trimmed of a trailing slash', function () {
    $this->put(route('projects.impersonate-url.update', $this->project), [
        'impersonate_base_url' => 'https://app.example.com/impersonate/take/',
    ])->assertRedirect();

    expect($this->project->fresh()->impersonate_base_url)->toBe('https://app.example.com/impersonate/take');
});

test('an impersonate url with an {id} token is accepted and stored verbatim', function () {
    $this->put(route('projects.impersonate-url.update', $this->project), [
        'impersonate_base_url' => 'https://app.example.com/impersonate/{id}/take',
    ])->assertRedirect()->assertSessionHasNoErrors();

    expect($this->project->fresh()->impersonate_base_url)->toBe('https://app.example.com/impersonate/{id}/take');
});

test('the impersonate base url must be a valid url', function () {
    $this->put(route('projects.impersonate-url.update', $this->project), [
        'impersonate_base_url' => 'not-a-url',
    ])->assertSessionHasErrors('impersonate_base_url');
});

test('a null impersonate base url clears the setting', function () {
    $this->project->update(['impersonate_base_url' => 'https://app.example.com/take']);

    $this->put(route('projects.impersonate-url.update', $this->project), [
        'impersonate_base_url' => null,
    ])->assertRedirect();

    expect($this->project->fresh()->impersonate_base_url)->toBeNull();
});

test('a user cannot set the impersonate base url on another teams project', function () {
    // Create the foreign project while acting as its owner so the HasTeamScope saving
    // hook stamps the stranger's team_id (otherwise it would inherit our team).
    $stranger = User::factory()->withPersonalTeam()->create();
    $this->actingAs($stranger);
    $strangerProject = Project::factory()->create(['team_id' => $stranger->currentTeam->id]);

    $this->actingAs($this->user);

    // The global team scope hides the foreign project from route-model binding entirely.
    $this->put(route('projects.impersonate-url.update', $strangerProject), [
        'impersonate_base_url' => 'https://evil.example.com/take',
    ])->assertNotFound();

    expect($strangerProject->fresh()->impersonate_base_url)->toBeNull();
});
