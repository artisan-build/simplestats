<?php

use App\Providers\AppServiceProvider;
use Spatie\Health\Checks\Checks\DatabaseCheck;
use Spatie\Health\Checks\Checks\HorizonCheck;
use Spatie\Health\Facades\Health;

// The Horizon check asks Horizon's master supervisor repository whether a
// supervisor is running. Horizon is Redis-only, so on any other queue driver
// the answer is "no" forever and the health report stays red for an
// installation that is in fact perfectly healthy.
function checksRegisteredFor(string $connection, array $connectionConfig): array
{
    config([
        'queue.default' => $connection,
        "queue.connections.{$connection}" => $connectionConfig,
    ]);

    Health::clearChecks();

    (new AppServiceProvider(app()))->boot();

    return Health::registeredChecks()->map(fn ($check): string => $check::class)->all();
}

test('the Horizon check is registered when the queue runs on Redis', function (): void {
    expect(checksRegisteredFor('redis', ['driver' => 'redis', 'queue' => 'default']))
        ->toContain(HorizonCheck::class);
});

test('the Horizon check is left out when the queue does not run on Redis', function (string $driver): void {
    $checks = checksRegisteredFor('somewhere', ['driver' => $driver, 'queue' => 'default']);

    expect($checks)->not->toContain(HorizonCheck::class)
        ->and($checks)->toContain(DatabaseCheck::class);
})->with(['sqs', 'cloud', 'database', 'beanstalkd', 'sync']);

test('every other check is registered either way', function (): void {
    $withHorizon = checksRegisteredFor('redis', ['driver' => 'redis', 'queue' => 'default']);
    $withoutHorizon = checksRegisteredFor('sqs', ['driver' => 'sqs', 'queue' => 'default']);

    expect(array_values(array_diff($withHorizon, $withoutHorizon)))->toBe([HorizonCheck::class])
        ->and(array_diff($withoutHorizon, $withHorizon))->toBe([]);
});
