<?php

namespace App\Models;

use App\Concerns\HasApiPlainTokens;
use App\Concerns\HasTeamScope;
use App\Events\InstanceCreated;
use App\Events\InstanceDeleted;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Instance extends Authenticatable
{
    use HasApiPlainTokens;
    use HasFactory;
    use HasTeamScope;

    const TOKEN_NAME = 'API Token';

    protected $casts = [
        'stats_starting_at' => 'date',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'project_id',
        'client_version',
        'stats_starting_at',
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

    public function statsUsers(): HasMany
    {
        return $this->hasMany(StatsUser::class);
    }

    public function statsLogins(): HasMany
    {
        return $this->hasMany(StatsLogin::class);
    }

    public function statsPayments(): HasMany
    {
        return $this->hasMany(StatsPayment::class);
    }
}
