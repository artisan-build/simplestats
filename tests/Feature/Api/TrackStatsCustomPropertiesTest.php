<?php

use App\Helpers\AppHelper;
use App\Models\Project;
use App\Models\StatsCustomProperty;
use App\Models\StatsCustomPropertyName;
use App\Models\StatsUser;
use App\Models\StatsVisitor;
use App\Models\User;
use Database\Seeders\TestUserSeeder;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\DB;

beforeEach(function (): void {
    $this->seed(TestUserSeeder::class);
    $this->user = User::where('email', config('app.test.user')['email'])->firstOrFail();
    $this->project = Project::first();
    $this->token = $this->project->createToken('test-token')->plainTextToken;

    // TestUserSeeder leaves the test user on the web guard; forget it so the
    // Bearer token (the project) wins over Sanctum's stateful session check.
    auth()->forgetGuards();
});

function ctx(?Authenticatable $project): void
{
    auth()->setUser($project);
    AppHelper::setTeamContextFromAuth($project);
    Context::add('project_id', $project->id);
    Context::add('request_url', '/api/v1/stats-user');
}

function userPayload(array $overrides = []): array
{
    return array_merge([
        'id' => '4242',
        'time' => now()->format('Y-m-d H:i:s P'),
        'ip' => fake()->ipv4(),
        'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36',
    ], $overrides);
}

test('tracking a user with properties stores normalised name, value and pivot', function (): void {
    $this->postJson('/api/v1/stats-user', userPayload([
        'properties' => ['ab_test' => 'B', 'company' => 'Acme Inc'],
    ]), apiHeaders($this->token))->assertOk();

    ctx($this->project);

    $user = StatsUser::where('id', '4242')->first();
    expect($user)->not->toBeNull();

    $properties = StatsCustomProperty::where('propertyable_type', 'stats_user')
        ->where('propertyable_id', $user->id)
        ->with(['name', 'value'])
        ->get();

    expect($properties)->toHaveCount(2);
    expect($properties->pluck('value.value', 'name.name')->all())
        ->toBe(['ab_test' => 'B', 'company' => 'Acme Inc']);
});

test('re-tracking a user overwrites the property value (current-value semantics)', function (): void {
    $this->postJson('/api/v1/stats-user', userPayload([
        'properties' => ['ab_test' => 'A'],
    ]), apiHeaders($this->token))->assertOk();

    $this->postJson('/api/v1/stats-user', userPayload([
        'properties' => ['ab_test' => 'B'],
    ]), apiHeaders($this->token))->assertOk();

    ctx($this->project);

    $user = StatsUser::where('id', '4242')->first();
    $properties = StatsCustomProperty::where('propertyable_type', 'stats_user')
        ->where('propertyable_id', $user->id)
        ->with('value')
        ->get();

    expect($properties)->toHaveCount(1)
        ->and($properties->first()->value->value)->toBe('B');
});

test('tracking a visitor with properties stores a visitor-bound pivot row', function (): void {
    $hash = str()->random(32);

    $this->postJson('/api/v1/stats-visitor', [
        'visitor_hash' => $hash,
        'time' => now()->format('Y-m-d H:i:s P'),
        'ip' => fake()->ipv4(),
        'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36',
        'properties' => ['ab_test' => 'B'],
    ], apiHeaders($this->token))->assertOk();

    ctx($this->project);

    $visitor = StatsVisitor::where('visitor_hash', $hash)->first();
    expect($visitor)->not->toBeNull();

    $property = StatsCustomProperty::where('propertyable_type', 'stats_visitor')
        ->where('propertyable_id', $visitor->id)
        ->with(['name', 'value'])
        ->first();

    expect($property)->not->toBeNull()
        ->and($property->name->name)->toBe('ab_test')
        ->and($property->value->value)->toBe('B');
});

test('the property endpoint records properties for an existing user', function (): void {
    $this->postJson('/api/v1/stats-user', userPayload(), apiHeaders($this->token))->assertOk();

    $this->postJson('/api/v1/stats-custom-properties', [
        'stats_user_id' => '4242',
        'time' => now()->format('Y-m-d H:i:s P'),
        'properties' => ['ab_test' => 'B'],
    ], apiHeaders($this->token))
        ->assertOk()
        ->assertJson(['id' => '4242'])
        ->assertJsonMissingPath('visitor_hash');

    ctx($this->project);

    $user = StatsUser::where('id', '4242')->first();
    $property = StatsCustomProperty::where('propertyable_type', 'stats_user')
        ->where('propertyable_id', $user->id)
        ->with(['name', 'value'])
        ->first();

    expect($property)->not->toBeNull()
        ->and($property->name->name)->toBe('ab_test')
        ->and($property->value->value)->toBe('B');
});

test('the property endpoint records properties for an existing visitor', function (): void {
    $hash = str()->random(32);

    $this->postJson('/api/v1/stats-visitor', [
        'visitor_hash' => $hash,
        'time' => now()->format('Y-m-d H:i:s P'),
        'ip' => fake()->ipv4(),
        'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36',
    ], apiHeaders($this->token))->assertOk();

    $this->postJson('/api/v1/stats-custom-properties', [
        'visitor_hash' => $hash,
        'time' => now()->format('Y-m-d H:i:s P'),
        'properties' => ['ab_test' => 'B'],
    ], apiHeaders($this->token))
        ->assertOk()
        ->assertJson(['visitor_hash' => $hash])
        ->assertJsonMissingPath('id');

    ctx($this->project);

    $visitor = StatsVisitor::where('visitor_hash', $hash)->first();
    $property = StatsCustomProperty::where('propertyable_type', 'stats_visitor')
        ->where('propertyable_id', $visitor->id)
        ->with(['name', 'value'])
        ->first();

    expect($property)->not->toBeNull()
        ->and($property->name->name)->toBe('ab_test')
        ->and($property->value->value)->toBe('B');
});

test('the property endpoint drops properties for an unknown visitor', function (): void {
    $this->postJson('/api/v1/stats-custom-properties', [
        'visitor_hash' => str()->random(32),
        'time' => now()->format('Y-m-d H:i:s P'),
        'properties' => ['ab_test' => 'B'],
    ], apiHeaders($this->token))->assertOk();

    ctx($this->project);

    expect(StatsVisitor::count())->toBe(0)
        ->and(StatsCustomProperty::count())->toBe(0);
});

test('the property endpoint requires a user id or a visitor hash', function (): void {
    $this->postJson('/api/v1/stats-custom-properties', [
        'time' => now()->format('Y-m-d H:i:s P'),
        'properties' => ['ab_test' => 'B'],
    ], apiHeaders($this->token))->assertStatus(422);
});

test('the property endpoint requires time', function (): void {
    $this->postJson('/api/v1/stats-custom-properties', [
        'stats_user_id' => '4242',
        'properties' => ['ab_test' => 'B'],
    ], apiHeaders($this->token))
        ->assertStatus(422)
        ->assertJsonValidationErrors('time');
});

test('an out-of-order update with an older time does not overwrite a newer value', function (): void {
    $this->postJson('/api/v1/stats-user', userPayload(), apiHeaders($this->token))->assertOk();

    $this->postJson('/api/v1/stats-custom-properties', [
        'stats_user_id' => '4242',
        'time' => now()->format('Y-m-d H:i:s P'),
        'properties' => ['ab_test' => 'C'],
    ], apiHeaders($this->token))->assertOk();

    // a stale update, e.g. a retried job that was dispatched before the one above
    $this->postJson('/api/v1/stats-custom-properties', [
        'stats_user_id' => '4242',
        'time' => now()->subHour()->format('Y-m-d H:i:s P'),
        'properties' => ['ab_test' => 'B'],
    ], apiHeaders($this->token))->assertOk();

    ctx($this->project);

    $user = StatsUser::where('id', '4242')->first();
    $properties = StatsCustomProperty::where('propertyable_type', 'stats_user')
        ->where('propertyable_id', $user->id)
        ->with('value')
        ->get();

    expect($properties)->toHaveCount(1)
        ->and($properties->first()->value->value)->toBe('C');
});

test('an update with a newer time overwrites an older value', function (): void {
    $this->postJson('/api/v1/stats-user', userPayload(), apiHeaders($this->token))->assertOk();

    $this->postJson('/api/v1/stats-custom-properties', [
        'stats_user_id' => '4242',
        'time' => now()->subHour()->format('Y-m-d H:i:s P'),
        'properties' => ['ab_test' => 'A'],
    ], apiHeaders($this->token))->assertOk();

    $this->postJson('/api/v1/stats-custom-properties', [
        'stats_user_id' => '4242',
        'time' => now()->format('Y-m-d H:i:s P'),
        'properties' => ['ab_test' => 'B'],
    ], apiHeaders($this->token))->assertOk();

    ctx($this->project);

    $user = StatsUser::where('id', '4242')->first();
    $properties = StatsCustomProperty::where('propertyable_type', 'stats_user')
        ->where('propertyable_id', $user->id)
        ->with('value')
        ->get();

    expect($properties)->toHaveCount(1)
        ->and($properties->first()->value->value)->toBe('B');
});

test('distinct property names are not capped per project', function (): void {
    ctx($this->project);

    $statsUser = StatsUser::factory()->create(['project_id' => $this->project->id]);

    $properties = [];
    for ($i = 0; $i < 60; $i++) {
        $properties["prop_{$i}"] = "value_{$i}";
    }

    $statsUser->recordCustomProperties($properties);

    expect(StatsCustomPropertyName::where('project_id', $this->project->id)->count())->toBe(60);
});

/**
 * Simulates a parallel ingest worker winning the pivot insert race (SIMPLESTATS-77): the
 * creating hook fires between recordCustomProperties' firstOrNew lookup and its insert,
 * so the raw insert lands exactly inside that window and the pivot insert hits the unique
 * constraint. Fires only once so the retry attempt goes through cleanly (in production
 * the winner's row survives, here it is rolled back together with the savepoint).
 */
function insertConflictingCustomPropertyRowOnCreating(): void
{
    $fired = false;

    StatsCustomProperty::creating(function (StatsCustomProperty $property) use (&$fired): void {
        if ($fired) {
            return;
        }

        $fired = true;

        DB::table('stats_custom_properties')->insert([
            'team_id' => $property->team_id,
            'project_id' => $property->project_id,
            'propertyable_type' => $property->propertyable_type,
            'propertyable_id' => $property->propertyable_id,
            'stats_custom_property_name_id' => $property->stats_custom_property_name_id,
            'stats_custom_property_value_id' => $property->stats_custom_property_value_id,
            'time' => now()->subHour()->format('Y-m-d H:i:s'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    });
}

test('a parallel worker winning the pivot insert race does not fail the property update', function (): void {
    ctx($this->project);

    $visitor = StatsVisitor::factory()->create(['project_id' => $this->project->id]);

    insertConflictingCustomPropertyRowOnCreating();

    $visitor->recordCustomProperties(['ab_test' => 'B']);

    $properties = StatsCustomProperty::where('propertyable_type', 'stats_visitor')
        ->where('propertyable_id', $visitor->id)
        ->with(['name', 'value'])
        ->get();

    expect($properties)->toHaveCount(1)
        ->and($properties->first()->name->name)->toBe('ab_test')
        ->and($properties->first()->value->value)->toBe('B');
});

test('non-scalar and empty property values are skipped', function (): void {
    ctx($this->project);

    $statsUser = StatsUser::factory()->create(['project_id' => $this->project->id]);

    $statsUser->recordCustomProperties([
        'good' => 'value',
        'nested' => ['not', 'allowed'],
        'empty' => '   ',
        'flag' => true,
    ]);

    $stored = StatsCustomProperty::where('propertyable_type', 'stats_user')
        ->where('propertyable_id', $statsUser->id)
        ->with(['name', 'value'])
        ->get()
        ->pluck('value.value', 'name.name')
        ->all();

    expect($stored)->toBe(['good' => 'value', 'flag' => 'true']);
});
