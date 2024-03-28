<?php

namespace App\Console\Commands;

use App\Actions\Fortify\CreateNewUser as CreateNewUserAction;
use Illuminate\Console\Command;

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
    public function handle()
    {
        $user = app(CreateNewUserAction::class)->create([
            'name' => $this->argument('name'),
            'email' => $this->argument('email'),
            'password' => $this->argument('password'),
            'password_confirmation' => $this->argument('password'),
            'terms' => true,
        ]);

        $user->email_verified_at = now();
        $user->save();

        $this->info("User {$user->name} created successfully.");

        return self::SUCCESS;
    }
}
