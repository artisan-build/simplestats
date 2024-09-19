<?php

namespace App\Helpers;

class CacheHelper
{
    public static function teamKey(string $key): string
    {
        return $key.'_'.auth()->user()->team->id;
    }
}
