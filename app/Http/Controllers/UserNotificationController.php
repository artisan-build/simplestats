<?php

namespace App\Http\Controllers;

use App\Enums\UserNotificationFrequency;
use App\Enums\UserNotificationType;
use App\Http\Requests\UpdateUserNotificationRequest;
use App\Models\UserNotification;
use App\Notifications\ProjectReportNotification;
use App\Services\StatsService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use function Illuminate\Support\defer;

class UserNotificationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $notifications = UserNotification::withWhereHas('notifiable',
            fn ($query) => $query->where('team_id', $request->user()->currentTeam->id)
        )->where('user_id', $request->user()->id)->get()
            ->sortBy(fn ($n) => optional($n->notifiable)->created_at)
            ->groupBy('type')
            ->sortBy(function ($group, $key) {
                return array_search($key, UserNotificationType::sorting());
            });

        return Inertia::render('Notifications/Index', [
            'notifications' => $notifications,
            'notificationTypes' => UserNotificationType::toArray(),
            'notificationStatsTypes' => StatsService::asOptions(),
            'notificationFrequencies' => UserNotificationFrequency::toOptions(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateUserNotificationRequest $request, UserNotification $notification)
    {
        $notification->update($request->validated());

        return back()->with('flash', [
            'banner' => 'Setting updated',
        ]);
    }

    /**
     * Trigger the report manually.
     */
    public function trigger(Request $request, UserNotification $notification)
    {
        if ($notification->frequency === UserNotificationFrequency::DISABLED) {
            return back()->with('flash', [
                'bannerStyle' => 'danger',
                'banner' => 'You must choose a frequency to send a report.',
            ]);
        }

        $notification->load('user', 'notifiable');

        defer(fn () => $notification->user->notify(new ProjectReportNotification($notification, now(), auth()->user()->currentTeam)));

        return back()->with('flash', [
            'banner' => 'Report sent',
        ]);
    }
}
