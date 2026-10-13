<?php

use App\Services\SubscriptionService;

/**
 * These invariants are relied on across the app:
 * - UserBase::getPlanConfig() uses $plans[0] as the free/default plan and the
 *   last plan as the "highest" plan (local + plan_auto_max_users).
 * - The landing page pricing slider sorts plans by their monthly event volume.
 * Adding or reordering plans must keep all of these intact.
 */
$plans = fn () => config('spark.billables.user.plans', []);

it('keeps the free plan first', function () use ($plans): void {
    expect($plans()[0]['name'])->toBe(SubscriptionService::PLAN_FREE);
});

it('keeps the highest event plan last', function () use ($plans): void {
    $list = $plans();
    $maxEvents = collect($list)->max(fn ($plan): mixed => $plan['options']['Events']);

    expect(end($list)['options']['Events'])->toBe($maxEvents);
});

it('orders plans by ascending monthly events', function () use ($plans): void {
    $events = collect($plans())->map(fn ($plan): mixed => $plan['options']['Events'])->all();

    expect($events)->toBe(collect($events)->sort()->values()->all());
});

it('exposes every key the app and pricing slider depend on', function () use ($plans): void {
    foreach ($plans() as $plan) {
        expect($plan)->toHaveKeys(['name', 'short_description', 'monthly_id', 'yearly_id', 'features', 'options']);
        expect($plan['options'])->toHaveKeys(['Events', 'ApiRateLimit']);
        expect($plan['options']['Events'])->toBeInt();
    }
});
