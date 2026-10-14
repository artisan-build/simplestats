<?php

use Dotenv\Repository\Adapter\ArrayAdapter;
use Dotenv\Repository\RepositoryBuilder;
use Illuminate\Support\Env;
use Tests\TestCase;

uses(TestCase::class);

// The shared disk is where a data import upload waits for the queue worker that
// reads it. On one server that is the local disk, which is what SimpleStats has
// always done. On a platform that runs web and queue in separate containers the
// local disk is private to each, so the attached bucket has to be used instead.
function sharedDiskWith(array $env, array $server): string
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
        return (require dirname(__DIR__, 2).'/config/filesystems.php')['shared'];
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

function cloudDiskConfig(array ...$disks): string
{
    return json_encode($disks);
}

test('the shared disk is the local disk when nothing is configured', function (): void {
    expect(sharedDiskWith([], []))->toBe('local');
});

test('the shared disk is the bucket Laravel Cloud attached', function (): void {
    expect(sharedDiskWith(
        ['LARAVEL_CLOUD_DISK_CONFIG' => cloudDiskConfig(['disk' => 's3', 'is_default' => false])],
        ['LARAVEL_CLOUD' => '1'],
    ))->toBe('s3');
});

test('the shared disk prefers the bucket marked as the default disk', function (): void {
    expect(sharedDiskWith(
        ['LARAVEL_CLOUD_DISK_CONFIG' => cloudDiskConfig(
            ['disk' => 'uploads', 'is_default' => false],
            ['disk' => 'storage', 'is_default' => true],
        )],
        ['LARAVEL_CLOUD' => '1'],
    ))->toBe('storage');
});

test('the shared disk stays local on Laravel Cloud without an attached bucket', function (): void {
    expect(sharedDiskWith([], ['LARAVEL_CLOUD' => '1']))->toBe('local');
});

test('the attached bucket is ignored when the installation is not on Laravel Cloud', function (): void {
    expect(sharedDiskWith(
        ['LARAVEL_CLOUD_DISK_CONFIG' => cloudDiskConfig(['disk' => 's3', 'is_default' => true])],
        [],
    ))->toBe('local');
});

test('SHARED_DISK overrides the detected disk', function (): void {
    expect(sharedDiskWith(
        [
            'SHARED_DISK' => 'imports',
            'LARAVEL_CLOUD_DISK_CONFIG' => cloudDiskConfig(['disk' => 's3', 'is_default' => true]),
        ],
        ['LARAVEL_CLOUD' => '1'],
    ))->toBe('imports');
});
