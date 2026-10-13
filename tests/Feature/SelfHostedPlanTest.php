<?php

use App\Models\Project;
use App\Models\User;
use App\Services\SubscriptionService;

beforeEach(function () {
    config(['app.app_type' => 'default']);
    $this->app->detectEnvironment(fn () => 'production');

    $this->user = User::factory()->withPersonalTeam()->create();
    $this->team = $this->user->currentTeam;
});

it('puts every self-hosted user on the unlimited plan, even in production', function () {
    $plan = $this->user->sparkPlan();

    expect($plan->name)->toBe(SubscriptionService::PLAN_SELF_HOSTED)
        ->and($plan->options['Events'])->toBe(SubscriptionService::UNLIMITED_AMOUNT)
        ->and($plan->options[Project::class])->toBe(SubscriptionService::UNLIMITED_AMOUNT);
});

it('never reaches the monthly event limit on self-hosted', function () {
    $this->team->update(['events_count_monthly' => 50_000_000]);

    $service = app(SubscriptionService::class);

    expect($service->hasReachedMonthlyEventLimit($this->team->refresh(), 1_000))->toBeFalse()
        ->and($service->getPlanAlarm($this->team))->toBe([]);
});
