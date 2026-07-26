<?php

use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

function seedRecoverableUser(): array
{
    $user = User::factory()->withPersonalTeam()->create([
        'email' => 'recover@example.com',
        'last_login_at' => '2020-01-01 00:00:00',
        'inactive_since' => '2021-02-02 00:00:00',
    ]);

    $team = $user->personalTeam();
    $project = Project::factory()->create(['team_id' => $team->id]);

    // The team owner is not in team_user (ownership is teams.user_id); attach a
    // member so the membership pivot actually has a row to dump.
    $member = User::factory()->create();
    $team->users()->attach($member, ['role' => 'editor']);

    $now = now()->toDateTimeString();

    DB::table('stats_track_sources')->insert([
        'id' => 7001, 'team_id' => $team->id, 'name' => 'newsletter',
        'created_at' => $now, 'updated_at' => $now,
    ]);

    $visitorId = DB::table('stats_visitors')->insertGetId([
        'team_id' => $team->id, 'project_id' => $project->id,
        'visitor_hash' => 'hash-abc', 'time' => $now, 'time_utc' => $now,
    ]);

    DB::table('stats_users')->insert([
        'id' => 'cust-user-1', 'team_id' => $team->id, 'project_id' => $project->id,
        'stats_track_source_id' => 7001, 'time' => $now, 'time_utc' => $now,
    ]);

    DB::table('stats_logins')->insert([
        'stats_user_id' => 'cust-user-1', 'team_id' => $team->id, 'project_id' => $project->id,
        'time' => $now, 'time_utc' => $now, 'counter' => 1,
    ]);

    DB::table('stats_payments')->insert([
        'id' => 'pay-1', 'stats_user_id' => 'cust-user-1', 'team_id' => $team->id, 'project_id' => $project->id,
        'gross' => 100, 'net' => 80, 'currency' => 'EUR', 'time' => $now, 'time_utc' => $now,
        'counter' => 1, 'stats_visitor_id' => $visitorId,
    ]);

    $nameId = DB::table('stats_custom_property_names')->insertGetId([
        'team_id' => $team->id, 'project_id' => $project->id, 'name' => 'plan',
        'created_at' => $now, 'updated_at' => $now,
    ]);
    $valueId = DB::table('stats_custom_property_values')->insertGetId([
        'team_id' => $team->id, 'project_id' => $project->id,
        'stats_custom_property_name_id' => $nameId, 'value' => 'pro',
        'created_at' => $now, 'updated_at' => $now,
    ]);
    DB::table('stats_custom_properties')->insert([
        'team_id' => $team->id, 'project_id' => $project->id,
        'propertyable_type' => 'stats_user', 'propertyable_id' => 'cust-user-1',
        'stats_custom_property_name_id' => $nameId, 'stats_custom_property_value_id' => $valueId,
        'time' => $now, 'created_at' => $now, 'updated_at' => $now,
    ]);

    DB::table('user_notifications')->insert([
        'uuid' => (string) Str::uuid(),
        'user_id' => $user->id, 'notifiable_type' => 'App\\Models\\Project', 'notifiable_id' => $project->id,
        'frequency' => 'weekly', 'type' => 'report', 'settings' => json_encode(['tracking' => ['registrations' => true]]),
        'created_at' => $now, 'updated_at' => $now,
    ]);

    DB::table('custom_stats_range_presets')->insert([
        'uuid' => (string) Str::uuid(),
        'user_id' => $user->id, 'name' => 'Last 14 days', 'range_amount' => 14, 'range_type' => 'day',
        'created_at' => $now, 'updated_at' => $now,
    ]);

    return ['user' => $user, 'team' => $team, 'project' => $project];
}

function runDump(string $identifier, array $options = []): string
{
    $path = storage_path('app/test-recovery-'.bin2hex(random_bytes(4)).'.sql');

    test()->artisan('app:dump-user-recovery', array_merge(['user' => $identifier, '--output' => $path], $options))
        ->assertSuccessful();

    $sql = File::get($path);
    File::delete($path);

    return $sql;
}

it('dumps the full user graph as ordered INSERT statements', function () {
    seedRecoverableUser();

    $sql = runDump('recover@example.com');

    foreach ([
        'users', 'teams', 'team_user', 'projects', 'stats_track_sources',
        'stats_visitors', 'stats_users', 'stats_logins', 'stats_payments',
        'stats_custom_property_names', 'stats_custom_property_values', 'stats_custom_properties',
        'user_notifications', 'custom_stats_range_presets',
    ] as $table) {
        expect($sql)->toContain('INSERT INTO "'.$table.'"');
    }

    expect($sql)->toContain('BEGIN;')->toContain('COMMIT;');
});

it('orders parents before their children', function () {
    seedRecoverableUser();

    $sql = runDump('recover@example.com');

    $pos = fn (string $table) => strpos($sql, 'INSERT INTO "'.$table.'"');

    expect($pos('users'))->toBeLessThan($pos('teams'));
    expect($pos('teams'))->toBeLessThan($pos('projects'));
    expect($pos('stats_track_sources'))->toBeLessThan($pos('projects'));
    expect($pos('projects'))->toBeLessThan($pos('stats_users'));
    expect($pos('stats_users'))->toBeLessThan($pos('stats_logins'));
    expect($pos('stats_users'))->toBeLessThan($pos('stats_payments'));
});

it('resets last_login_at and inactive_since on the user insert', function () {
    seedRecoverableUser();

    $sql = runDump('recover@example.com');

    expect($sql)->toContain('NOW()');
    // The sentinel backup values must not survive into the dump.
    expect($sql)->not->toContain('2020-01-01 00:00:00');
    expect($sql)->not->toContain('2021-02-02 00:00:00');
});

it('keeps the composite (id, project_id) key on stats inserts', function () {
    seedRecoverableUser();

    $sql = runDump('recover@example.com');

    expect($sql)->toContain('INSERT INTO "stats_users" ("id", "team_id", "project_id"');
    expect($sql)->toContain("'cust-user-1'");
});

it('excludes billing by default and includes it with --with-billing', function () {
    ['user' => $user] = seedRecoverableUser();
    $user->subscriptions()->create([
        'type' => 'default', 'paddle_id' => 'sub_recover', 'status' => 'active',
    ]);

    expect(runDump('recover@example.com'))->not->toContain('INSERT INTO "subscriptions"');
    expect(runDump('recover@example.com', ['--with-billing' => true]))->toContain('INSERT INTO "subscriptions"');
});

it('resolves the user by numeric id too', function () {
    ['user' => $user] = seedRecoverableUser();

    expect(runDump((string) $user->id))->toContain('recover@example.com');
});

it('fails for an unknown user', function () {
    $this->artisan('app:dump-user-recovery', ['user' => 'ghost@example.com'])->run();
})->throws(RuntimeException::class);
