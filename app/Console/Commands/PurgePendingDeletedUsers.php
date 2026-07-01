<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Laravel\Jetstream\Contracts\DeletesUsers;

class PurgePendingDeletedUsers extends Command
{
    protected $signature = 'app:purge-pending-deleted-users';

    protected $description = 'Hard-deletes users that were marked for deletion longer than the retention window ago';

    // Grace window between the soft mark (CheckInactiveUsers) and the irreversible,
    // cascading deletion. Long enough to notice and restore a wrongly-flagged account.
    private const PURGE_AFTER_MONTHS = 2;

    public function handle(DeletesUsers $deletesUsers): void
    {
        User::onlyTrashed()
            ->where('deleted_at', '<', now()->subMonths(self::PURGE_AFTER_MONTHS))
            ->each(fn (User $user) => $deletesUsers->delete($user));
    }
}
