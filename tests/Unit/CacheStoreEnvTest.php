<?php

use Dotenv\Repository\Adapter\ArrayAdapter;
use Dotenv\Repository\RepositoryBuilder;
use Illuminate\Support\Env;
use Tests\TestCase;

uses(TestCase::class);

// Laravel 11 renamed CACHE_DRIVER to CACHE_STORE. Platforms that provision the cache
// for you set the new name, older .env files carry the old one, and config/cache.php
// has to read both or it silently falls back to the file store.
function cacheDefaultWith(array $values): string
{
    $repository = RepositoryBuilder::createWithNoAdapters()
        ->addAdapter(ArrayAdapter::class)
        ->make();

    foreach ($values as $key => $value) {
        $repository->set($key, $value);
    }

    $property = new ReflectionProperty(Env::class, 'repository');
    $original = Env::getRepository();
    $property->setValue(null, $repository);

    try {
        return (require dirname(__DIR__, 2).'/config/cache.php')['default'];
    } finally {
        $property->setValue(null, $original);
    }
}

test('the cache store is read from CACHE_STORE', function (): void {
    expect(cacheDefaultWith(['CACHE_STORE' => 'redis']))->toBe('redis');
});

test('CACHE_STORE wins over a leftover CACHE_DRIVER', function (): void {
    expect(cacheDefaultWith(['CACHE_STORE' => 'redis', 'CACHE_DRIVER' => 'file']))->toBe('redis');
});

test('an existing CACHE_DRIVER still works on its own', function (): void {
    expect(cacheDefaultWith(['CACHE_DRIVER' => 'array']))->toBe('array');
});

test('the cache store falls back to the file store', function (): void {
    expect(cacheDefaultWith([]))->toBe('file');
});
