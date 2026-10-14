<?php

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
