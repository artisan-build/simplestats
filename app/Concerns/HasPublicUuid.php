<?php

namespace App\Concerns;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Support\Str;

/**
 * Public uuid route key on top of the internal bigint primary key: urls expose
 * the uuid while all foreign keys and queries keep using the id.
 */
trait HasPublicUuid
{
    use HasUuids;

    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    /**
     * Random v4 instead of the trait's default v7: the uuid is a public route
     * key, a time-ordered uuid would leak the row's creation time.
     */
    public function newUniqueId(): string
    {
        return (string) Str::uuid();
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}
