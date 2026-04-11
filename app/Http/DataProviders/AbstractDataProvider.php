<?php

namespace App\Http\DataProviders;

abstract class AbstractDataProvider
{
    abstract public function toArray(): array;
}
