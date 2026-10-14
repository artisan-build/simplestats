<?php

use Illuminate\Support\Arr;

test('every config value survives config:cache serialization', function (): void {
    $unserializable = collect(Arr::dot(config()->all()))
        ->filter(function (mixed $value): bool {
            try {
                eval(var_export($value, true).';');

                return false;
            } catch (Throwable) {
                return true;
            }
        })
        ->keys();

    expect($unserializable)->toBeEmpty();
});
