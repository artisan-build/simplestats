<?php

// storage/ is part of the repository: a plain `git clone` has to be able to run
// `composer install` (which boots the framework via package:discover) without
// anyone creating directories first.
test('the storage directories a fresh checkout needs are present and writable', function (string $relative): void {
    $path = dirname(__DIR__, 2).'/storage/'.$relative;

    expect(is_dir($path))->toBeTrue($path.' is missing');
    expect(is_writable($path))->toBeTrue($path.' is not writable');
})->with([
    'app',
    'app/public',
    'framework/cache/data',
    'framework/sessions',
    'framework/testing',
    'framework/views',
    'logs',
]);
