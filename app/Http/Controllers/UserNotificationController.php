<?php

namespace App\Http\Controllers;

use App\Models\UserNotification;
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
            'notifications' => UserNotification::with('notifiable')->where('user_id', $request->user()->id)->get()
                ->groupBy('notifiable_type'),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, UserNotification $userNotification)
    {
        //
    }
}
