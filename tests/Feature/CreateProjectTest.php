<?php

use App\Models\Project;
use App\Models\User;
use App\Services\StatsService;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->user = User::factory()->withPersonalTeam()->create();
    actingAs($this->user);
});

test('domain is required when creating a project', function () {
    $this->post(route('projects.store'), [
        'name' => 'My Project',
        'usage' => 1,
    ])->assertSessionHasErrors('domain');
});

test('domain is normalized to a bare host', function () {
    $this->post(route('projects.store'), [
        'name' => 'My Project',
        'domain' => 'https://example.com/some/path?utm=foo',
        'usage' => 1,
    ])->assertSessionHasNoErrors();

    expect(Project::where('name', 'My Project')->value('domain'))->toBe('example.com');
});

test('a plain domain is accepted as-is', function () {
    $this->post(route('projects.store'), [
        'name' => 'Plain Domain',
        'domain' => 'example.org',
        'usage' => 1,
    ])->assertSessionHasNoErrors();

    expect(Project::where('name', 'Plain Domain')->value('domain'))->toBe('example.org');
});

test('a single label without a tld is rejected', function () {
    $this->post(route('projects.store'), [
        'name' => 'No TLD',
        'domain' => 'asdfa',
        'usage' => 1,
    ])->assertSessionHasErrors('domain');
});

test('localhost is accepted as a domain', function () {
    $this->post(route('projects.store'), [
        'name' => 'Local Project',
        'domain' => 'http://localhost:8000',
        'usage' => 1,
    ])->assertSessionHasNoErrors();

    expect(Project::where('name', 'Local Project')->value('domain'))->toBe('localhost');
});

test('recurring tracking requires registrations and payments', function () {
    $this->post(route('projects.store'), [
        'name' => 'Recurring Only',
        'domain' => 'example.com',
        'usage' => StatsService::TRACKING_USAGE_BITS_VISITORS
            | StatsService::TRACKING_USAGE_BITS_SUBSCRIPTION,
    ])->assertSessionHasErrors('usage');
});

test('recurring tracking is accepted together with registrations and payments', function () {
    $this->post(route('projects.store'), [
        'name' => 'Full Recurring',
        'domain' => 'example.com',
        'usage' => StatsService::TRACKING_USAGE_BITS_VISITORS
            | StatsService::TRACKING_USAGE_BITS_USERS
            | StatsService::TRACKING_USAGE_BITS_PAYMENTS
            | StatsService::TRACKING_USAGE_BITS_SUBSCRIPTION,
    ])->assertSessionHasNoErrors();
});
