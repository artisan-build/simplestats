<?php

namespace App\Models;

use App\Concerns\HasInstanceScope;
use App\Concerns\HasTeamScope;
use App\Enums\DataImportType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Junges\TrackableJobs\Models\TrackedJob;

class DataImport extends Model
{
    use HasFactory;
    use HasTeamScope;

    protected $casts = [
        'type' => DataImportType::class,
    ];

    protected $fillable = [
        'project_id',
        'instance_id',
        'created_by',
        'id_from',
        'id_to',
        'type',
        'rows_imported',
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

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    public function trackedJob(): MorphOne
    {
        return $this->morphOne(TrackedJob::class, 'trackable');
    }
}
