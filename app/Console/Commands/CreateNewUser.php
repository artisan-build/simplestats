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
        $user = DB::transaction(function (): User {
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

        return self::SUCCESS;
    }
}
