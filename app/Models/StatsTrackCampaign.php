<?php

namespace App\Models;

use App\Concerns\HasPublicUuid;
use App\Concerns\HasTeamScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StatsTrackCampaign extends Model
{
    use HasPublicUuid;
    use HasTeamScope;

    protected $fillable = [
        'team_id',
        'name',
        'campaign_id',
    ];

    protected $hidden = [
        'id',
        'team_id',
        'campaign_id',
    ];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }
}
