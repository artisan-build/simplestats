<?php

namespace Database\Seeders;

use App\Actions\Fortify\CreateNewUser;
use App\Helpers\AppHelper;
use App\Models\Project;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;

class TestUserSeeder extends Seeder
{
    const DEFAULT_CURRENCY = 'EUR';

    public function run(): void
    {
        Cache::clear();

        $user = User::withoutEvents(function () {
            $isDemo = app()->environment('demo');
            $testUser = config('app.test.user');
            $user = resolve(CreateNewUser::class)->create([
                'name' => $testUser['name'],
                'email' => $testUser['email'],
                'password' => $testUser['password'],
                'password_confirmation' => $testUser['password'],
                'terms' => true,
                'settings' => [
                    'tourCompleted' => true,
                ],
            ]);

            if (! $isDemo) {
                $user->makeAdmin();
            }

            $user->markEmailAsVerified();

            $user->switchTeam($user->personalTeam());
            $user->currentTeam->update([
                'currency' => self::DEFAULT_CURRENCY,
                'timezone' => 'Europe/Berlin',
            ]);

            return $user;
        });

        auth()->setUser($user);
        AppHelper::setTeamContextFromAuth($user);

        // Outside the test suite the demo project also tracks subscriptions so the
        // Subscriptions area has data; tests keep the default bitmask (no subscription).
        $usage = Project::USAGE_VISITORS
            | Project::USAGE_USERS
            | Project::USAGE_PAYMENTS;

        if (! app()->environment('testing')) {
            $usage |= Project::USAGE_SUBSCRIPTION;
        }

        Project::factory()
            ->count(1)
            ->state(['name' => 'My great App', 'usage' => $usage])
            ->for(Team::find($user->current_team_id))
            ->create();
    }
}
