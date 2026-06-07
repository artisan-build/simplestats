<?php

namespace App\Contracts;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * An entity that can carry custom properties (a StatsUser or a StatsVisitor).
 * Implemented via the HasCustomProperties trait.
 */
interface Propertyable
{
    public function customProperties(): MorphMany;

    public function recordCustomProperties(mixed $properties, ?CarbonImmutable $time = null): void;
}
