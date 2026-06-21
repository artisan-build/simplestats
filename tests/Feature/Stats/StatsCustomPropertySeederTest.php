<?php

use App\Models\StatsCustomProperty;
use App\Models\StatsCustomPropertyName;
use App\Models\StatsCustomPropertyValue;
use Database\Seeders\DatabaseSeeder;

test('the database seeder produces custom property demo data', function () {
    $this->seed(DatabaseSeeder::class);

    // Lookup tables are populated for both user- and visitor-bound properties.
    expect(StatsCustomPropertyName::where('name', 'company')->exists())->toBeTrue()
        ->and(StatsCustomPropertyName::where('name', 'ab_test')->exists())->toBeTrue()
        ->and(StatsCustomPropertyValue::where('value', 'A')->exists())->toBeTrue();

    // The polymorphic pivot holds both user- and visitor-bound rows.
    expect(StatsCustomProperty::where('propertyable_type', 'stats_user')->exists())->toBeTrue()
        ->and(StatsCustomProperty::where('propertyable_type', 'stats_visitor')->exists())->toBeTrue();
});
