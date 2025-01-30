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
        // first delete already warned users, so that they do not get another notification
        $this->deleteInactiveUsers();

        // notify inactive users
        $this->notifyInactiveUsers();
    }

    private function deleteInactiveUsers(): void
    {
        User::query()
            ->whereDoesntHave('subscriptions', function ($query) {
                $query->where('status', 'active');
            })
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

    private function notifyInactiveUsers(): void
    {
        User::query()
            ->whereDoesntHave('subscriptions', function ($query) {
                $query->where('status', 'active');
            })
            ->where('last_login_at', '<', now()->subDays(50))
            ->whereNull('inactive_since')
            ->each(function (User $user) {
                $user->notify(new UserInactiveNotification($user, 10));
                $user->update(['inactive_since' => now()]);
            });

        User::query()
            ->whereDoesntHave('subscriptions', function ($query) {
                $query->where('status', 'active');
            })
            ->where('inactive_since', '<', now()->subDays(9))
            ->each(function (User $user) {
                $user->notify(new UserInactiveNotification($user, 1));
            });
    }
}
