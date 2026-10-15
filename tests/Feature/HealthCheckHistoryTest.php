<?php

use Illuminate\Support\Facades\Schema;
use Spatie\Health\Checks\Checks\DatabaseCheck;
use Spatie\Health\Facades\Health;
use Spatie\Health\Models\HealthCheckResultHistoryItem;

// The scheduler runs health:check every minute in production, so a result store that
// cannot accept what spatie/laravel-health writes turns into an exception a minute.
test('a health check result is written to the history table', function (): void {
    Health::clearChecks();
    Health::checks([DatabaseCheck::new()]);

    $this->artisan('health:check')->assertSuccessful();

    $item = HealthCheckResultHistoryItem::query()->sole();

    expect($item->check_name)->toBe('Database')
        ->and($item->check_label)->toBe('Database')
        ->and($item->status)->toBe('ok');
});

// Installs that migrated before the vendor migrations were squashed already have both
// columns, so adding them unconditionally aborts the deploy with a duplicate column
// error. A migrated test database is in exactly that state, so running the migration
// again here is the upgrade those installs get.
test('the migration leaves an install that already has the columns alone', function (): void {
    $table = 'health_check_result_history_items';

    $columnsNamed = fn (string $column): int => collect(Schema::getColumns($table))
        ->where('name', $column)
        ->count();

    expect($columnsNamed('check_name'))->toBe(1)
        ->and($columnsNamed('check_label'))->toBe(1);

    $migration = require database_path('migrations/2026_10_14_090000_add_check_name_and_label_to_health_check_result_history_items_table.php');

    $migration->up();

    expect($columnsNamed('check_name'))->toBe(1)
        ->and($columnsNamed('check_label'))->toBe(1);
});
