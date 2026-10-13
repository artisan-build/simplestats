<?php

use App\Models\User;
use Illuminate\Support\Facades\DB;

it('hard-deletes users marked longer than the retention window ago', function (): void {
    $user = User::factory()->withPersonalTeam()->create([
        'deleted_at' => now()->subMonths(3),
    ]);
    $personalTeam = $user->personalTeam();

    $this->artisan('app:purge-pending-deleted-users');

    expect($user->fresh())->toBeNull();
    expect($personalTeam->fresh())->toBeNull();
});

it('keeps users marked within the retention window', function (): void {
    $user = User::factory()->withPersonalTeam()->create([
        'deleted_at' => now()->subMonth(),
    ]);

    $this->artisan('app:purge-pending-deleted-users');

    expect($user->fresh())->not->toBeNull();
});

it('ignores users that are not marked for deletion', function (): void {
    $user = User::factory()->withPersonalTeam()->create(['deleted_at' => null]);

    $this->artisan('app:purge-pending-deleted-users');

    expect($user->fresh())->not->toBeNull();
});

it('archives the purged user into deleted_models', function (): void {
    $user = User::factory()->withPersonalTeam()->create([
        'deleted_at' => now()->subMonths(3),
    ]);

    $this->artisan('app:purge-pending-deleted-users');

    expect(DB::table('deleted_models')
        ->where('model', (new User)->getMorphClass())
        ->where('key', $user->id)
        ->exists())->toBeTrue();
});
