<?php

namespace App\Models;

use App\Concerns\HasInstanceScope;
use App\Concerns\HasTeamScope;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

abstract class BaseStat extends Model
{
    use HasFactory;
    use HasTeamScope;
    use HasInstanceScope;

    public $timestamps = false;

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'time' => 'datetime',
    ];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function instance(): BelongsTo
    {
        return $this->belongsTo(Instance::class);
    }

    public function time(): Attribute
    {
        return Attribute::make(
            set: function ($value) {
                return [
                    'time' => Carbon::parse($value)->tz(auth()->user()->team->timezone),
                    'time_utc' => $value,
                ];
            },
        );
    }
}
