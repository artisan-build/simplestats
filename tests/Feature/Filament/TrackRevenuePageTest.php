<?php

use App\Filament\Pages\TrackRevenue;
use App\Models\Project;
use App\Models\StatsPayment;
use App\Models\StatsUser;
use App\Models\Team;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->skipUnlessSaas();

    $this->admin = User::factory()->create(['is_admin' => true]);
    $this->actingAs($this->admin);

    $this->team = Team::factory()->create([
        'currency' => 'EUR',
        'timezone' => 'Europe/Berlin',
    ]);
    $this->project = Project::factory()->create(['team_id' => $this->team->id]);
});

test('track revenue page loads for admin users', function (): void {
    Livewire::test(TrackRevenue::class)->assertOk();
});

test('tracking revenue for an existing user creates a stats user and payment', function (): void {
    $targetUser = User::factory()->create();

    Livewire::test(TrackRevenue::class)
        ->fillForm([
            'project_id' => $this->project->id,
            'user_type' => 'existing',
            'user_id' => $targetUser->id,
            'location_country' => 'Germany (DE)',
            'gross' => 9900,
            'net' => 8316,
            'currency' => 'EUR',
            'user_time' => now()->format('Y-m-d H:i:s'),
            'payment_time' => now()->format('Y-m-d H:i:s'),
        ])
        ->call('track')
        ->assertNotified()
        ->assertHasNoFormErrors();

    $statsUser = StatsUser::withoutGlobalScopes()->find((string) $targetUser->id);
    expect($statsUser)->not->toBeNull()
        ->and($statsUser->project_id)->toBe($this->project->id)
        ->and($statsUser->team_id)->toBe($this->team->id)
        ->and($statsUser->stats_location_country_id)->not->toBeNull();

    $payment = StatsPayment::withoutGlobalScopes()
        ->where('stats_user_id', (string) $targetUser->id)
        ->first();

    expect($payment)->not->toBeNull()
        ->and($payment->id)->toBeString()
        ->and($payment->stats_user_id)->toBe((string) $targetUser->id)
        ->and($payment->gross)->toBe(9900.0)
        ->and($payment->net)->toBe(8316.0)
        ->and($payment->currency)->toBe('EUR')
        ->and($payment->project_id)->toBe($this->project->id)
        ->and($payment->team_id)->toBe($this->team->id);
});

test('tracking revenue for an external buyer uses a uuid stats user id', function (): void {
    Livewire::test(TrackRevenue::class)
        ->fillForm([
            'project_id' => $this->project->id,
            'user_type' => 'external',
            'gross' => 5000,
            'net' => 5000,
            'currency' => 'EUR',
            'user_time' => now()->format('Y-m-d H:i:s'),
            'payment_time' => now()->format('Y-m-d H:i:s'),
        ])
        ->call('track')
        ->assertNotified()
        ->assertHasNoFormErrors();

    $payment = StatsPayment::withoutGlobalScopes()
        ->where('project_id', $this->project->id)
        ->first();

    expect($payment)->not->toBeNull()
        ->and($payment->gross)->toBe(5000.0)
        ->and(is_numeric($payment->stats_user_id))->toBeFalse();

    $statsUser = StatsUser::withoutGlobalScopes()->find($payment->stats_user_id);
    expect($statsUser)->not->toBeNull()
        ->and($statsUser->project_id)->toBe($this->project->id);
});

test('non-admin users cannot access the track revenue page', function (): void {
    $user = User::factory()->create(['is_admin' => false]);

    $this->actingAs($user)
        ->get('/admin/track-revenue')
        ->assertForbidden();
});
