<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Notifications\UserDeletedNotification;
use App\Notifications\UserInactiveNotification;
use Illuminate\Console\Command;

class CheckInactiveUsers extends Command
{
    protected $signature = 'app:check-inactive-users';

    protected $description = 'Marks inactive users for deletion after the warning period';

    public function handle(): void
    {
        // first mark already warned users, so that they do not get another notification
        $this->markInactiveUsers();

        // notify inactive users
        $this->notifyInactiveUsers();
    }

    private function markInactiveUsers(): void
    {
        // Soft delete only: the user, their teams and stats stay intact for the
        // grace window so a wrongly-flagged account can be restored without a backup.
        // The SoftDeletes scope hides already-marked users from these queries; the
        // real cascading delete runs later in app:purge-pending-deleted-users.
        User::query()
            ->whereDoesntHave('subscriptions', function ($query) {
                $query->where('status', 'active');
            })
            ->where('inactive_since', '<', now()->subDays(10))
            ->each(function (User $user) {
                $user->notify(new UserDeletedNotification($user));
                $user->delete();
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
