<?php

namespace App\Console\Commands;

use App\Models\Instance;
use App\Models\StatsLogin;
use App\Models\Team;
use App\Models\User;
use Illuminate\Console\Command;

class CleanOldUsersAndTeamsCommand extends Command
{
    protected $signature = 'app:clean-old-users-and-teams';

    protected $description = 'Removes old users and empty teams';

    public function handle(): void
    {
        $this->removeOldUsers();
        $this->removeEmptyTeams();
    }

    private function removeOldUsers(): void
    {
        $laracraftTeam = Team::query()->where('name', 'Laracraft')->first();
        $laracraftInstance = Instance::query()->where([
            'team_id' => $laracraftTeam->id,
        ])->first();

        $oldUsers = StatsLogin::query()
            ->select('stats_user_id')
            ->where('instance_id', $laracraftInstance->id)
            ->groupBy('stats_user_id')
            ->havingRaw('MAX(time) < ?', [now()->subDays(60)->toDateString()])
            ->pluck('stats_user_id');

        User::query()->whereIn('id', $oldUsers)->delete();
    }

    private function removeEmptyTeams(): void
    {
        Team::with('users', 'owner')->each(function ($team) {
            if ($team->users->isEmpty() && ! $team->owner) {
                $team->delete();
            }
        });
    }
}
