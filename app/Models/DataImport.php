<?php

namespace App\Models;

use App\Concerns\HasPublicUuid;
use App\Concerns\HasTeamScope;
use App\Enums\DataImportType;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Junges\TrackableJobs\Models\TrackedJob;

class DataImport extends Model
{
    use HasFactory;
    use HasPublicUuid;
    use HasTeamScope;

    protected $hidden = [
        'id',
        'team_id',
        'project_id',
        'created_by',
    ];

    protected $casts = [
        'type' => DataImportType::class,
        'rolled_back_at' => 'datetime',
    ];

    protected $appends = [
        'rows_progress',
    ];

    public function rowsProgress(): Attribute
    {
        return Attribute::make(
            get: fn () => round(($this->rows_imported / $this->rows_max) * 100),
        );

    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    public function trackedJob(): MorphOne
    {
        return $this->morphOne(TrackedJob::class, 'trackable');
    }
}
