<?php

namespace App\Models;

use App\Concerns\HasTeamScope;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdSpend extends Model
{
    use HasFactory;
    use HasTeamScope;

    protected $fillable = [
        'team_id',
        'project_id',
        'stats_track_campaign_id',
        'amount',
        'currency',
        'starts_on',
        'ends_on',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'starts_on' => 'immutable_date',
            'ends_on' => 'immutable_date',
        ];
    }

    public function statsTrackCampaign(): BelongsTo
    {
        return $this->belongsTo(StatsTrackCampaign::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Cents allocated to [$windowStart, $windowEnd] by day overlap, since a
     * spend entry's budget is spread evenly across its own run (starts_on..ends_on).
     */
    public function allocatedFor(CarbonImmutable $windowStart, CarbonImmutable $windowEnd): int
    {
        $start = $this->starts_on->startOfDay();
        $end = $this->ends_on->startOfDay();

        $totalDays = $start->diffInDays($end) + 1;

        $overlapStart = $start->max($windowStart->startOfDay());
        $overlapEnd = $end->min($windowEnd->startOfDay());

        if ($overlapStart->greaterThan($overlapEnd)) {
            return 0;
        }

        $overlapDays = $overlapStart->diffInDays($overlapEnd) + 1;

        return (int) round($this->amount * $overlapDays / $totalDays);
    }
}
