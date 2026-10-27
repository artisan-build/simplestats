<?php

use App\Models\User;

it('makes the first user an admin', function (): void {
    $this->artisan('app:create-new-user', [
        'name' => 'Ada Lovelace',
        'email' => 'ada@example.com',
        'password' => 'password-123',
    ])->assertSuccessful();

    expect(User::query()->where('email', 'ada@example.com')->sole()->isAdmin())->toBeTrue();
});

it('does not make later users an admin', function (): void {
    $this->artisan('app:create-new-user', [
        'name' => 'Ada Lovelace',
        'email' => 'ada@example.com',
        'password' => 'password-123',
    ])->assertSuccessful();

    $this->artisan('app:create-new-user', [
        'name' => 'Grace Hopper',
        'email' => 'grace@example.com',
        'password' => 'password-123',
    ])->assertSuccessful();

    expect(User::query()->where('email', 'grace@example.com')->sole()->isAdmin())->toBeFalse();
});

it('creates a user with a verified email and a personal team', function (): void {
    $this->artisan('app:create-new-user', [
        'name' => 'Ada Lovelace',
        'email' => 'ada@example.com',
        'password' => 'password-123',
    ])->assertSuccessful();

    $user = User::query()->where('email', 'ada@example.com')->sole();

    expect($user->name)->toBe('Ada Lovelace');
    expect($user->email_verified_at)->not->toBeNull();
    expect($user->personalTeam()->name)->toBe("Ada's Team");
});

it('asks self-hosters for a github star after creating the first user', function (): void {
    config(['app.app_type' => 'default']);

    $this->artisan('app:create-new-user', [
        'name' => 'Ada Lovelace',
        'email' => 'ada@example.com',
        'password' => 'password-123',
    ])->expectsOutputToContain('https://github.com/simplestats-io/app')->assertSuccessful();

    $this->artisan('app:create-new-user', [
        'name' => 'Grace Hopper',
        'email' => 'grace@example.com',
        'password' => 'password-123',
    ])->doesntExpectOutputToContain('https://github.com/simplestats-io/app')->assertSuccessful();
});

it('does not ask for a github star on the saas', function (): void {
    config(['app.app_type' => 'saas']);

    $this->artisan('app:create-new-user', [
        'name' => 'Ada Lovelace',
        'email' => 'ada@example.com',
        'password' => 'password-123',
    ])->doesntExpectOutputToContain('https://github.com/simplestats-io/app')->assertSuccessful();
});
