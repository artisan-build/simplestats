<?php

use Dotenv\Repository\Adapter\ArrayAdapter;
use Dotenv\Repository\RepositoryBuilder;
use Illuminate\Support\Env;
use Tests\TestCase;

uses(TestCase::class);

// A server you run yourself keeps its daily backup on the local disk, which is
// what SimpleStats has always done. A platform that backs the database up for
// you, and whose local disk is gone on the next deploy, gets backups off until
// the operator asks for them, and then they land on the attached bucket.
function backupConfigWith(array $env, array $server): array
{
    $repository = RepositoryBuilder::createWithNoAdapters()
        ->addAdapter(ArrayAdapter::class)
        ->make();

    foreach ($env as $key => $value) {
        $repository->set($key, $value);
    }

    $property = new ReflectionProperty(Env::class, 'repository');
    $original = Env::getRepository();
    $property->setValue(null, $repository);

    $previous = array_intersect_key($_SERVER, $server);

    foreach ($server as $key => $value) {
        $_SERVER[$key] = $value;
    }

    try {
        return require dirname(__DIR__, 2).'/config/backup.php';
    } finally {
        $property->setValue(null, $original);

        foreach (array_keys($server) as $key) {
            unset($_SERVER[$key]);
        }

        foreach ($previous as $key => $value) {
            $_SERVER[$key] = $value;
        }
    }
}

// Laravel Cloud sets LARAVEL_CLOUD=1 and passes the attached buckets in
// LARAVEL_CLOUD_DISK_CONFIG.
function cloudEnv(array $extra = []): array
{
    return array_merge([
        'LARAVEL_CLOUD_DISK_CONFIG' => json_encode([['disk' => 's3', 'is_default' => false]]),
    ], $extra);
}

function cloudServer(): array
{
    return ['LARAVEL_CLOUD' => '1'];
}

test('backups are enabled and stored locally by default', function (): void {
    $config = backupConfigWith([], []);

    expect($config['enabled'])->toBeTrue()
        ->and($config['backup']['destination']['disks'])->toBe(['local'])
        ->and($config['monitor_backups'][0]['disks'])->toBe(['local']);
});

test('backups are disabled by default on Laravel Cloud', function (): void {
    expect(backupConfigWith(cloudEnv(), cloudServer())['enabled'])->toBeFalse();
});

test('backups target the attached bucket on Laravel Cloud', function (): void {
    $config = backupConfigWith(cloudEnv(), cloudServer());

    expect($config['backup']['destination']['disks'])->toBe(['s3'])
        ->and($config['monitor_backups'][0]['disks'])->toBe(['s3']);
});

test('BACKUP_ENABLED turns backups on where they default to off', function (): void {
    expect(backupConfigWith(cloudEnv(['BACKUP_ENABLED' => 'true']), cloudServer())['enabled'])->toBeTrue();
});

test('BACKUP_ENABLED turns backups off where they default to on', function (): void {
    expect(backupConfigWith(['BACKUP_ENABLED' => 'false'], [])['enabled'])->toBeFalse();
});

test('BACKUP_DISKS still wins over the detected disk', function (): void {
    $config = backupConfigWith(cloudEnv(['BACKUP_DISKS' => 'local, s3']), cloudServer());

    expect($config['backup']['destination']['disks'])->toBe(['local', 's3'])
        ->and($config['monitor_backups'][0]['disks'])->toBe(['local', 's3']);
});
