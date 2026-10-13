<?php

use Rector\Caching\ValueObject\Storage\FileCacheStorage;
use Rector\CodeQuality\Rector\Empty_\SimplifyEmptyCheckOnEmptyArrayRector;
use Rector\CodeQuality\Rector\Identical\FlipTypeControlToUseExclusiveTypeRector;
use Rector\CodeQuality\Rector\Identical\SimplifyBoolIdenticalTrueRector;
use Rector\CodeQuality\Rector\Isset_\IssetOnPropertyObjectToPropertyExistsRector;
use Rector\Config\RectorConfig;
use Rector\DeadCode\Rector\ClassMethod\RemoveDuplicatedReturnSelfDocblockRector;
use Rector\TypeDeclaration\Rector\StmtsAwareInterface\SafeDeclareStrictTypesRector;
use RectorLaravel\Rector\StaticCall\CarbonToDateFacadeRector;
use RectorLaravel\Set\LaravelSetList;

return RectorConfig::configure()
    // fixed path so CI can cache it between runs
    ->withCache(cacheDirectory: '/tmp/rector', cacheClass: FileCacheStorage::class)
    ->withPaths([
        __DIR__.'/app',
        __DIR__.'/bootstrap/app.php',
        __DIR__.'/config',
        __DIR__.'/database',
        __DIR__.'/routes',
        __DIR__.'/tests',
    ])
    ->withImportNames(removeUnusedImports: true)
    ->withPhpSets()
    ->withPreparedSets(
        deadCode: true,
        codeQuality: true,
        typeDeclarations: true,
        privatization: true,
        earlyReturn: true,
    )
    ->withComposerBased(laravel: true)
    ->withSets([
        LaravelSetList::LARAVEL_CODE_QUALITY,
        LaravelSetList::LARAVEL_COLLECTION,
        LaravelSetList::LARAVEL_TYPE_DECLARATIONS,
    ])
    ->withSkip([
        // Date is bound to CarbonImmutable, so this would silently turn mutable Carbon immutable
        CarbonToDateFacadeRector::class,
        FlipTypeControlToUseExclusiveTypeRector::class,
        // isset() also null-checks and sees Eloquent magic attributes, property_exists() does neither
        IssetOnPropertyObjectToPropertyExistsRector::class,
        RemoveDuplicatedReturnSelfDocblockRector::class,
        SafeDeclareStrictTypesRector::class,
        SimplifyBoolIdenticalTrueRector::class,
        SimplifyEmptyCheckOnEmptyArrayRector::class,
    ]);
