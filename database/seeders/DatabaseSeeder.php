<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Actions\Fortify\CreateNewUser;
use App\Models\Project;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $user = (new CreateNewUser())->create([
            'name' => 'Zacharias Creutznacher',
            'email' => 'zacharias.creutznacher@gmail.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'terms' => true,
        ]);

        $user->switchTeam($user->personalTeam());

        Project::factory()->count(10)->create([
            'team_id' => $user->current_team_id,
        ]);

        //TODO create instances for these projects!!
    }
}
