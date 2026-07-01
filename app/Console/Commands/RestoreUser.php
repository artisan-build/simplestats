<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class RestoreUser extends Command
{
    protected $signature = 'app:restore-user {user : Email or ID of the marked user}';

    protected $description = 'Restores a user that was marked for deletion and resets the inactivity fields';

    public function handle(): int
    {
        $identifier = $this->argument('user');

        $query = User::onlyTrashed();
        $user = is_numeric($identifier)
            ? $query->where('id', $identifier)->first()
            : $query->where('email', $identifier)->first();

        if (! $user) {
            $this->error("No user marked for deletion found for [{$identifier}].");

            return self::FAILURE;
        }

        $user->restore();
        $user->update([
            'inactive_since' => null,
            'last_login_at' => now(),
        ]);

        $this->info("Restored {$user->email} (id {$user->id}).");

        return self::SUCCESS;
    }
}
