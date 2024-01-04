<?php

namespace App\Models;

use App\Concerns\HasInstanceScope;
use App\Concerns\HasTeamScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StatsUser extends Model
{
    use HasFactory;
    use HasTeamScope;
    use HasInstanceScope;

    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'track_source',
        'track_medium',
        'track_campaign',
        'track_term',
        'track_content',
        'created_at',
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
