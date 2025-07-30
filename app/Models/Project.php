<?php

namespace App\Models;

use App\Concerns\HasTeamScope;
use App\Contracts\NotifiableToUser;
use App\Enums\UserNotificationFrequency;
use App\Enums\UserNotificationType;
use App\Observers\ProjectObserver;
use App\Services\StatsService;
use App\ValueObjects\UserNotificationTypeObject;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Collection;

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

    public function userReportNotifications(): MorphMany
    {
        return $this->userNotifications()->where('type', UserNotificationType::PROJECT_REPORT->value);
    }

    public function userAlertNotifications(): MorphMany
    {
        return $this->userNotifications()->where('type', UserNotificationType::PROJECT_ALERT->value);
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

    public function getDefaultUserNotificationTypes(): Collection
    {
        return collect([
            new UserNotificationTypeObject(
                type: UserNotificationType::PROJECT_REPORT,
                frequency: UserNotificationFrequency::WEEKLY,
                settings: [
                    'tracking' => collect([
                        'visitors' => $this->hasTrackingUsageBits([StatsService::TRACKING_USAGE_BITS_VISITORS]),
                        'users' => $this->hasTrackingUsageBits([StatsService::TRACKING_USAGE_BITS_USERS]),
                        'payments' => $this->hasTrackingUsageBits([StatsService::TRACKING_USAGE_BITS_PAYMENTS]),
                    ])->filter()->toArray(),
                    'types' => [
                        StatsTrackReferer::class,
                        StatsPageEntry::class,
                        StatsLocationCountry::class,
                        StatsDevicePlatform::class,
                    ],
                ]
            ),
            new UserNotificationTypeObject(
                type: UserNotificationType::PROJECT_ALERT,
                frequency: null,
                settings: [
                    // even if they are enabled bits, they are deactivated by default (visitors are completely disabled)
                    'tracking' => collect([
                        'registrations' => $this->hasTrackingUsageBits([StatsService::TRACKING_USAGE_BITS_USERS]),
                        'payments' => $this->hasTrackingUsageBits([StatsService::TRACKING_USAGE_BITS_PAYMENTS]),
                    ])->filter()->map(fn () => false)->toArray(),
                ]
            ),
        ]);
    }
}
