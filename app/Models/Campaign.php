<?php

namespace App\Models;

use App\Concerns\HasTeamScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Campaign extends Model
{
    use HasFactory;
    use HasTeamScope;

    protected $fillable = [
        'team_id',
        'project_id',
        'name',
    ];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function statsTrackCampaigns(): HasMany
    {
        return $this->hasMany(StatsTrackCampaign::class);
    }

    public function adSpends(): HasMany
    {
        return $this->hasMany(AdSpend::class);
    }
}
