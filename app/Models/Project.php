<?php

namespace App\Models;

use App\Concerns\HasApiPlainTokens;
use App\Concerns\HasTeamScope;
use App\Contracts\NotifiableToUser;
use App\Enums\UserNotificationFrequency;
use App\Enums\UserNotificationType;
use App\Events\ProjectCreated;
use App\Events\ProjectDeleted;
use App\Observers\ProjectObserver;
use App\Services\StatsService;
use App\ValueObjects\UserNotificationTypeObject;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Collection;

#[ObservedBy(ProjectObserver::class)]
class Project extends Authenticatable implements NotifiableToUser
{
    use HasApiPlainTokens;
    use HasFactory;
    use HasTeamScope;

    const TOKEN_NAME = 'API Token';

    protected $casts = [
        'stats_starting_at' => 'date',
    ];

    protected $dispatchesEvents = [
        'created' => ProjectCreated::class,
        'deleted' => ProjectDeleted::class,
    ];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
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
                    'tracking' => collect([
                        'registrations' => $this->hasTrackingUsageBits([StatsService::TRACKING_USAGE_BITS_USERS]),
                        'payments' => $this->hasTrackingUsageBits([StatsService::TRACKING_USAGE_BITS_PAYMENTS]),
                    ])->filter()->toArray(),
                ]
            ),
        ]);
    }
}
