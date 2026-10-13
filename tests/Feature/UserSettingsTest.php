<?php

use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\DB;

beforeEach(function (): void {
    $this->user = User::factory()->withPersonalTeam()->create();
    $this->user->currentTeam->update(['currency' => 'USD', 'timezone' => 'UTC']);
    Project::factory()->create(['team_id' => $this->user->currentTeam->id]);
    $this->actingAs($this->user);
});

test('set dashboard preset persists a valid preset', function (): void {
    $this->from(route('dashboard'))
        ->post(route('user-settings.set-dashboard-preset'), ['preset' => 'expert'])
        ->assertRedirect(route('dashboard'));

    expect($this->user->fresh()->settings['dashboardPreset'])->toBe('expert');
});

test('set dashboard preset rejects an unknown preset', function (): void {
    $this->from(route('dashboard'))
        ->post(route('user-settings.set-dashboard-preset'), ['preset' => 'ultra'])
        ->assertSessionHasErrors('preset');

    expect($this->user->fresh()->settings['dashboardPreset'] ?? null)->toBeNull();
});

test('migration sets existing users to the expert preset and keeps other settings', function (): void {
    DB::table('users')->where('id', $this->user->id)->update([
        'settings' => json_encode(['tourCompleted' => true]),
    ]);

    $migration = require database_path('migrations/2026_06_22_115534_set_dashboard_preset_for_existing_users.php');
    $migration->up();

    $settings = $this->user->fresh()->settings;

    expect($settings['dashboardPreset'])->toBe('expert')
        ->and($settings['tourCompleted'])->toBeTrue();
});
