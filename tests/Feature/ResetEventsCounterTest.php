<?php

use App\Models\Project;
use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Seeders\TestUserSeeder;
use Illuminate\Support\Facades\Date;

beforeEach(function (): void {
    $this->seed(TestUserSeeder::class);
    $this->user = User::where('email', config('app.test.user')['email'])->firstOrFail();
    $this->project = Project::first();
});

test('reset events counter resets project events_count_monthly', function (): void {
    $team = $this->project->team;
    $team->update(['events_count_monthly' => 100]);
    $this->project->update(['events_count_monthly' => 60]);

    Date::setTestNow(CarbonImmutable::now()->startOfMonth());

    $this->artisan('app:reset-events-counter')->assertSuccessful();

    $team->refresh();
    $this->project->refresh();

    expect($team->events_count_monthly)->toBe(0)
        ->and($this->project->events_count_monthly)->toBe(0);
});

test('reset events counter does not reset on non-first day of month', function (): void {
    $team = $this->project->team;
    $team->update(['events_count_monthly' => 100, 'events_reset_at' => null]);
    $this->project->update(['events_count_monthly' => 60]);

    Date::setTestNow(CarbonImmutable::now()->startOfMonth()->addDay());

    $this->artisan('app:reset-events-counter')->assertSuccessful();

    $this->project->refresh();

    expect($this->project->events_count_monthly)->toBe(60);
});
