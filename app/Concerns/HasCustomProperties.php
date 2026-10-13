<?php

namespace App\Concerns;

use App\Models\StatsCustomProperty;
use App\Models\StatsCustomPropertyName;
use App\Models\StatsCustomPropertyValue;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Str;

trait HasCustomProperties
{
    /**
     * Composite name + value index length must stay within MySQL limits, so we
     * trim like HasStatsTracking does for the other tracking lookups.
     */
    public const CUSTOM_PROPERTY_MAX_LENGTH = 252;

    /**
     * The current custom properties of this entity.
     *
     * The extra project_id constraint is required for correctness: stats_users.id
     * carries the customer's user id, which is only unique per project, so the
     * morph match on id alone could cross projects within a team. Because the
     * constraint reads $this->project_id, eager loading this relation is only
     * safe for single-project model sets.
     *
     * @return MorphMany<StatsCustomProperty, $this>
     */
    public function customProperties(): MorphMany
    {
        return $this->morphMany(StatsCustomProperty::class, 'propertyable')
            ->where('stats_custom_properties.project_id', $this->project_id);
    }

    /**
     * Record the current set of properties for this entity. Each name + value
     * is normalised into the lookup tables and the pivot is upserted so that
     * every (entity, name) keeps exactly one current value. Tolerates raw API
     * input: anything but an array is ignored.
     *
     * $time is the time the values were current on the client (defaults to
     * now). Tracking runs through queues on both sides, so updates can arrive
     * out of order; an update older than the stored value is dropped instead
     * of overwriting a newer state.
     */
    public function recordCustomProperties(mixed $properties, ?CarbonImmutable $time = null): void
    {
        if (! is_array($properties)) {
            return;
        }

        // The time column is timezone-naive and the app default timezone
        // varies (ensureTeamTimezone), so store and compare explicitly in UTC.
        $time = ($time ?? now())->utc();

        foreach ($properties as $name => $value) {
            $name = $this->normalizeCustomProperty($name);
            $value = $this->normalizeCustomProperty($value);

            if ($name === null || $value === null) {
                continue;
            }

            $propertyName = StatsCustomPropertyName::firstOrCreate([
                'project_id' => $this->project_id,
                'name' => $name,
            ]);

            $propertyValue = StatsCustomPropertyValue::firstOrCreate([
                'project_id' => $this->project_id,
                'stats_custom_property_name_id' => $propertyName->id,
                'value' => $value,
            ]);

            // retried once because a parallel ingest worker can win the insert between
            // firstOrNew and save; the second attempt then finds the winner's row and
            // takes the update path (savepoint rationale: see TrackStatsUser)
            retry(2, function () use ($propertyName, $propertyValue, $time): void {
                $property = StatsCustomProperty::firstOrNew([
                    'project_id' => $this->project_id,
                    'propertyable_type' => $this->getMorphClass(),
                    'propertyable_id' => $this->id,
                    'stats_custom_property_name_id' => $propertyName->id,
                ]);

                if ($property->exists && CarbonImmutable::parse($property->time, 'UTC')->greaterThan($time)) {
                    return;
                }

                $property->fill([
                    'stats_custom_property_value_id' => $propertyValue->id,
                    'time' => $time->format('Y-m-d H:i:s'),
                ]);

                $property->newModelQuery()->withSavepointIfNeeded(fn () => $property->save());
            }, 0, fn ($e): bool => $e instanceof UniqueConstraintViolationException);
        }
    }

    /**
     * Coerce a scalar property key/value into a trimmed, length-capped string.
     * Non-scalars (nested arrays/objects) and empty strings are dropped.
     */
    protected function normalizeCustomProperty(mixed $value): ?string
    {
        if (is_bool($value)) {
            $value = $value ? 'true' : 'false';
        }

        if (! is_scalar($value)) {
            return null;
        }

        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        return Str::substr($value, 0, self::CUSTOM_PROPERTY_MAX_LENGTH);
    }
}
