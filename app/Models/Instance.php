<?php

namespace App\Models;

use App\Concerns\HasApiPlainTokens;
use App\Concerns\HasTeamScope;
use App\Events\InstanceCreated;
use App\Events\InstanceDeleted;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Instance extends Model
{
    use HasFactory;
    use HasApiPlainTokens;
    use HasTeamScope;

    const TOKEN_NAME_POSTFIX = ' Token';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'project_id',
    ];

    protected $dispatchesEvents = [
        'created' => InstanceCreated::class,
        'deleted' => InstanceDeleted::class,
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
