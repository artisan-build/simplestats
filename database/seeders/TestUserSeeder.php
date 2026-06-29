<?php

namespace Database\Seeders;

use App\Actions\Fortify\CreateNewUser;
use App\Helpers\AppHelper;
use App\Models\Project;
use App\Models\Team;
use App\Models\User;
use App\Services\StatsService;
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
            $user = app(CreateNewUser::class)->create([
                'name' => $testUser['name'],
                'email' => $testUser['email'],
                'password' => $testUser['password'],
                'password_confirmation' => $testUser['password'],
                'terms' => true,
                'settings' => [
                    'tourCompleted' => true,
                    'campaignsTourCompleted' => true,
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
        $usage = StatsService::TRACKING_USAGE_BITS_VISITORS
            | StatsService::TRACKING_USAGE_BITS_USERS
            | StatsService::TRACKING_USAGE_BITS_PAYMENTS;

        if (! app()->environment('testing')) {
            $usage |= StatsService::TRACKING_USAGE_BITS_SUBSCRIPTION;
        }

        Project::factory()
            ->count(1)
            ->state(['name' => 'My great App', 'usage' => $usage])
            ->for(Team::find($user->current_team_id))
            ->create();
    }
}
