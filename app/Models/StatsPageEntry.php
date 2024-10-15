<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class StatsPageEntry extends Model
{
    use HasFactory;

    public function name(): Attribute
    {
        // we truncate the name if it gets to long, as we only have a name varchar 255 colum
        // unfortunately a text column can't be a part of a unique compound index...
        return Attribute::make(
            set: fn (string $value) => Str::limit($value, 252),
        );
    }
}
