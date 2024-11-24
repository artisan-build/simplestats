<?php

namespace App\Console\Commands;

use App\Models\Team;
use App\Models\User;
use App\Notifications\UserDeletedNotification;
use App\Notifications\UserInactiveNotification;
use Illuminate\Console\Command;

class CheckInactiveUsers extends Command
{
    protected $signature = 'app:check-inactive-users';

    protected $description = 'Checks inactive users and removes empty teams';

    public function handle(): void
    {
        User::query()
            ->where('last_login_at', '<', now()->subDays(50))
            ->whereNull('inactive_since')
            ->each(function (User $user) {
                $user->notify(new UserInactiveNotification($user, 10));
                $user->update(['inactive_since' => now()]);
            });

        User::query()
            ->where('inactive_since', '<', now()->subDays(1))
            ->each(function (User $user) {
                $user->notify(new UserInactiveNotification($user, 1));
            });

        User::query()
            ->where('inactive_since', '<', now()->subDays(10))
            ->each(function (User $user) {
                $user->notify(new UserDeletedNotification($user));
                $user->delete();
            });

        Team::with('users', 'owner')->each(function ($team) {
            if ($team->users->isEmpty() && ! $team->owner) {
                $team->delete();
            }
        });
    }
}
