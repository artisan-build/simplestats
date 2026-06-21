<?php

use App\Helpers\AppHelper;
use App\Models\Project;
use App\Models\StatsCustomProperty;
use App\Models\StatsCustomPropertyName;
use App\Models\StatsCustomPropertyValue;
use App\Models\StatsUser;
use App\Models\StatsVisitor;
use Database\Seeders\TestUserSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    $this->seed(TestUserSeeder::class);
    $this->project = Project::first();

    // Mirror the ingest auth context so HasTeamScope resolves the team id.
    auth()->setUser($this->project);
    AppHelper::setTeamContextFromAuth($this->project);
    Context::add('project_id', $this->project->id);
});

function makeName(Project $project, string $name): StatsCustomPropertyName
{
    // team_id is set by the HasTeamScope saving hook (auth context from beforeEach)
    return StatsCustomPropertyName::create([
        'project_id' => $project->id,
        'name' => $name,
    ]);
}

function makeValue(StatsCustomPropertyName $name, string $value): StatsCustomPropertyValue
{
    return StatsCustomPropertyValue::create([
        'project_id' => $name->project_id,
        'stats_custom_property_name_id' => $name->id,
        'value' => $value,
    ]);
}

test('property tables have the expected schema', function () {
    expect(Schema::hasColumns('stats_custom_property_names', ['id', 'team_id', 'project_id', 'name']))->toBeTrue();
    expect(Schema::hasColumns('stats_custom_property_values', ['id', 'team_id', 'project_id', 'stats_custom_property_name_id', 'value']))->toBeTrue();
    expect(Schema::hasColumns('stats_custom_properties', [
        'id', 'team_id', 'project_id', 'propertyable_type', 'propertyable_id',
        'stats_custom_property_name_id', 'stats_custom_property_value_id',
    ]))->toBeTrue();
});

test('team_id is set automatically via HasTeamScope on save', function () {
    $name = makeName($this->project, 'ab_test');

    expect($name->team_id)->toBe($this->project->team_id);
});

test('a property keeps exactly one current value per entity and name', function () {
    $user = StatsUser::factory()->create(['project_id' => $this->project->id]);
    $name = makeName($this->project, 'ab_test');
    $free = makeValue($name, 'A');
    $pro = makeValue($name, 'B');

    $owner = [
        'project_id' => $this->project->id,
        'propertyable_type' => 'stats_user',
        'propertyable_id' => $user->id,
        'stats_custom_property_name_id' => $name->id,
    ];

    // First the entity is on "free", then it upgrades to "pro".
    StatsCustomProperty::updateOrCreate($owner, ['stats_custom_property_value_id' => $free->id, 'time' => now()]);
    StatsCustomProperty::updateOrCreate($owner, ['stats_custom_property_value_id' => $pro->id, 'time' => now()]);

    $properties = StatsCustomProperty::where($owner)->get();

    expect($properties)->toHaveCount(1)
        ->and($properties->first()->stats_custom_property_value_id)->toBe($pro->id);
});

test('the unique constraint blocks a duplicate entity-name row', function () {
    $user = StatsUser::factory()->create(['project_id' => $this->project->id]);
    $name = makeName($this->project, 'ab_test');
    $free = makeValue($name, 'A');
    $pro = makeValue($name, 'B');

    $owner = [
        'project_id' => $this->project->id,
        'propertyable_type' => 'stats_user',
        'propertyable_id' => $user->id,
        'stats_custom_property_name_id' => $name->id,
    ];

    StatsCustomProperty::create([...$owner, 'stats_custom_property_value_id' => $free->id, 'time' => now()]);

    expect(fn () => StatsCustomProperty::create([...$owner, 'stats_custom_property_value_id' => $pro->id, 'time' => now()]))
        ->toThrow(QueryException::class);
});

test('the same property name can hold one value per user and per visitor independently', function () {
    $user = StatsUser::factory()->create(['project_id' => $this->project->id]);
    $visitor = StatsVisitor::factory()->create(['project_id' => $this->project->id]);
    $name = makeName($this->project, 'ab_test');
    $variantA = makeValue($name, 'A');
    $variantB = makeValue($name, 'B');

    StatsCustomProperty::create([
        'project_id' => $this->project->id,
        'propertyable_type' => 'stats_user',
        'propertyable_id' => $user->id,
        'stats_custom_property_name_id' => $name->id,
        'stats_custom_property_value_id' => $variantA->id,
        'time' => now(),
    ]);

    // Same project + name, but a different entity type -> allowed.
    StatsCustomProperty::create([
        'project_id' => $this->project->id,
        'propertyable_type' => 'stats_visitor',
        'propertyable_id' => $visitor->id,
        'stats_custom_property_name_id' => $name->id,
        'stats_custom_property_value_id' => $variantB->id,
        'time' => now(),
    ]);

    expect(StatsCustomProperty::where('stats_custom_property_name_id', $name->id)->count())->toBe(2);
});

test('the morph relation resolves the owning entity', function () {
    $user = StatsUser::factory()->create(['project_id' => $this->project->id]);
    $name = makeName($this->project, 'plan');
    $value = makeValue($name, 'enterprise');

    $property = StatsCustomProperty::create([
        'project_id' => $this->project->id,
        'propertyable_type' => 'stats_user',
        'propertyable_id' => $user->id,
        'stats_custom_property_name_id' => $name->id,
        'stats_custom_property_value_id' => $value->id,
        'time' => now(),
    ]);

    expect($property->propertyable_type)->toBe('stats_user')
        ->and($property->value->value)->toBe('enterprise')
        ->and($property->name->name)->toBe('plan');
});
