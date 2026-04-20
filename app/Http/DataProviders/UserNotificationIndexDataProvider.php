<?php

namespace App\Http\DataProviders;

use App\Actions\CreateUserNotification;
use App\Enums\UserNotificationFrequency;
use App\Enums\UserNotificationType;
use App\Models\User;
use App\Models\UserNotification;
use App\Services\StatsService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class UserNotificationIndexDataProvider extends AbstractDataProvider
{
    public function __construct(
        private Request $request,
    ) {}

    public function toArray(): array
    {
        return [
            'notifications' => $this->loadNotifications($this->request->user()),
            'notificationTypes' => UserNotificationType::toArray(),
            'notificationStatsTypes' => StatsService::asOptions(),
            'notificationFrequencies' => UserNotificationFrequency::toOptions(),
        ];
    }

    private function loadNotifications(User $user): Collection
    {
        $user->currentTeam->projects->each(function ($project) use ($user) {
            app(CreateUserNotification::class)($user, $project);
        });

        return UserNotification::withWhereHas(
            'notifiable',
            fn ($query) => $query->where('team_id', $user->currentTeam->id)
        )
            ->where('user_id', $user->id)
            ->get()
            ->sortBy(fn ($n) => optional($n->notifiable)->created_at)
            ->groupBy('type')
            ->sortBy(fn ($group, $key) => array_search($key, UserNotificationType::sorting()));
    }
}
