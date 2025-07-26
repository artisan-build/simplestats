<?php

namespace App\Listeners;

use App\Notifications\WelcomeMailNotification;
use Illuminate\Auth\Events\Verified;

class EmailVerificationListener
{
    /**
     * Handle the event.
     */
    public function handle(Verified $event): void
    {
        $user = $event->user;
        $user->notify(new WelcomeMailNotification($user));
    }
}
