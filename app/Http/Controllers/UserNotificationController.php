<?php

namespace App\Http\Controllers;

use App\Enums\UserNotificationFrequency;
use App\Http\Requests\UpdateUserNotificationRequest;
use App\Models\UserNotification;
use App\Notifications\ProjectReportNotification;
use App\Services\StatsService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class UserNotificationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        return Inertia::render('Notifications/Index', [
            'notifications' => UserNotification::with([
                'notifiable' => fn ($query) => $query->withoutGlobalScope('team'),
            ])->where('user_id', $request->user()->id)->get()
                ->sortBy(fn ($n) => optional($n->notifiable)->created_at)
                ->groupBy([
                    'notifiable.team_id',
                    'notifiable_type',
                ]),
            'notificationTypes' => StatsService::asOptions(),
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
        $notification->load('user', 'notifiable');

        $notification->user->notify(new ProjectReportNotification($notification, now()));

        return back()->with('flash', [
            'banner' => 'Report sent',
        ]);
    }
}
