<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Jetstream\Events\TeamCreated;
use Laravel\Jetstream\Events\TeamDeleted;
use Laravel\Jetstream\Events\TeamUpdated;
use Laravel\Jetstream\Team as JetstreamTeam;

class Team extends JetstreamTeam
{
    use HasFactory;

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'personal_team' => 'boolean',
        'stats_starting_at' => 'date',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'currency',
        'personal_team',
        'stats_starting_at',
    ];

    /**
     * The event map for the model.
     *
     * @var array<string, class-string>
     */
    protected $dispatchesEvents = [
        'created' => TeamCreated::class,
        'updated' => TeamUpdated::class,
        'deleted' => TeamDeleted::class,
    ];

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    public function instances(): HasMany
    {
        return $this->hasMany(Instance::class);
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
