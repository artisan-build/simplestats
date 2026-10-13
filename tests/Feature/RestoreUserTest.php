<?php

use App\Models\User;
use Illuminate\Support\Carbon;

it('restores a marked user and resets the inactivity fields', function (): void {
    $user = User::factory()->create([
        'deleted_at' => now()->subDay(),
        'inactive_since' => now()->subDays(20),
        'last_login_at' => now()->subDays(70),
    ]);

    $this->artisan('app:restore-user', ['user' => $user->email])
        ->assertSuccessful();

    $fresh = $user->fresh();
    expect($fresh->deleted_at)->toBeNull();
    expect($fresh->inactive_since)->toBeNull();
    expect(Carbon::parse($fresh->last_login_at)->isToday())->toBeTrue();
});

it('resolves the marked user by numeric id too', function (): void {
    $user = User::factory()->create(['deleted_at' => now()->subDay()]);

    $this->artisan('app:restore-user', ['user' => (string) $user->id])
        ->assertSuccessful();

    expect($user->fresh()->deleted_at)->toBeNull();
});

it('fails when no marked user matches', function (): void {
    User::factory()->create(['deleted_at' => null, 'email' => 'active@example.com']);

    $this->artisan('app:restore-user', ['user' => 'active@example.com'])
        ->assertFailed();
});
