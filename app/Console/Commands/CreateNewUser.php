<?php

namespace App\Console\Commands;

use App\Actions\Fortify\CreateNewUser as CreateNewUserAction;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CreateNewUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:create-new-user {name} {email} {password}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new user with name, email, and password';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $isFirstUser = false;

        $user = DB::transaction(function () use (&$isFirstUser): User {
            // The very first user becomes an admin, otherwise a fresh self-hosted
            // install has nobody who can reach the admin panel: registration needs
            // a team invitation and nothing else sets is_admin.
            $isFirstUser = ! User::query()->exists();

            $user = resolve(CreateNewUserAction::class)->create([
                'name' => $this->argument('name'),
                'email' => $this->argument('email'),
                'password' => $this->argument('password'),
                'password_confirmation' => $this->argument('password'),
                'terms' => true,
            ]);

            $user->email_verified_at = now();
            $user->save();

            if ($isFirstUser) {
                $user->makeAdmin();
            }

            return $user;
        });

        $this->info("User {$user->name} created successfully.");

        // Creating the first user is the one manual step of every self-hosted
        // install (Docker included), so it is where we ask for a star. The URL is
        // only printed: on a server there is no browser to open.
        if ($isFirstUser && config('app.app_type') !== 'saas') {
            $this->newLine();
            $this->line('Enjoying '.config('app.name').'? Give us a star on GitHub: https://github.com/simplestats-io/app');
        }

        return self::SUCCESS;
    }
}
