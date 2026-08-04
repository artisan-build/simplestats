<?php

namespace App\Models;

use App\Concerns\HasPublicUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Jetstream\Events\TeamCreated;
use Laravel\Jetstream\Events\TeamDeleted;
use Laravel\Jetstream\Events\TeamUpdated;
use Laravel\Jetstream\Team as JetstreamTeam;

class Team extends JetstreamTeam
{
    use HasFactory;
    use HasPublicUuid;

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'personal_team' => 'boolean',
        'events_reset_at' => 'datetime',
        'ai_api_key' => 'encrypted',
    ];

    /**
     * The team is serialized to the frontend on the team settings page; the
     * decrypted BYOK key must never leave the backend.
     *
     * @var list<string>
     */
    protected $hidden = [
        'ai_api_key',
        'id',
        'user_id',
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

    /**
     * The owner used to resolve plan limits and options.
     *
     * Inactive owners are soft-deleted for a two month grace window while their
     * team, projects and API tokens keep running, so the plain `owner` relation
     * is null there. Plan resolution must survive that, otherwise every API
     * request of an otherwise healthy team fails.
     */
    public function billingOwner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id')->withTrashed();
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    public function tracksUsage(int $bit): bool
    {
        return $this->projects()
            ->whereRaw('(usage & ?) > 0', [$bit])
            ->exists();
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
