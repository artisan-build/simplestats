<?php

namespace App\Models;

use App\Concerns\HasTeamScope;
use App\Contracts\NotifiableToUser;
use App\Observers\ProjectObserver;
use App\Services\StatsService;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

#[ObservedBy(ProjectObserver::class)]
class Project extends Model implements NotifiableToUser
{
    use HasFactory;
    use HasTeamScope;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'usage',
    ];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function instances(): HasMany
    {
        return $this->hasMany(Instance::class);
    }

    public function userNotifications(): MorphMany
    {
        return $this->morphMany(UserNotification::class, 'notifiable');
    }

    public function hasTrackingUsageBits(array $bits): bool
    {
        foreach ($bits as $bit) {
            if (! ($bit & $this->usage)) {
                return false;
            }
        }

        return true;
    }

    public function getDefaultNotificationSettings(): array
    {
        return [
            'tracking' => array_filter([
                'visitors' => $this->hasTrackingUsageBits([StatsService::TRACKING_USAGE_BITS_VISITORS]),
                'users' => $this->hasTrackingUsageBits([StatsService::TRACKING_USAGE_BITS_USERS]),
                'payments' => $this->hasTrackingUsageBits([StatsService::TRACKING_USAGE_BITS_PAYMENTS]),
            ]),
            'types' => [
                StatsTrackReferer::class,
                StatsPageEntry::class,
                StatsLocationCountry::class,
                StatsDevicePlatform::class,
            ],
        ];
    }
}
