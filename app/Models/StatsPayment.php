<?php

namespace App\Models;

use App\Concerns\HasCounterByStatsUser;
use App\Concerns\HasInstanceScope;
use App\Concerns\HasStatsUserAssurance;
use App\Concerns\HasTeamScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StatsPayment extends Model
{
    use HasFactory;
    use HasTeamScope;
    use HasInstanceScope;
    use HasCounterByStatsUser;
    use HasStatsUserAssurance;

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    public $incrementing = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id',
        'stats_user_id',
        'gross',
        'net',
        'currency',
        'orig_gross',
        'orig_net',
        'orig_currency',
        'time',
    ];

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

    public function statsUser(): BelongsTo
    {
        return $this->belongsTo(StatsUser::class);
    }
}
