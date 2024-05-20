<?php

namespace App\Models;

use App\Concerns\HasInstanceScope;
use App\Concerns\HasTeamScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
        'time' => 'date',
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
}
