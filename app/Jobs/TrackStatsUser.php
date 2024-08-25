<?php

namespace App\Jobs;

use App\Models\StatsUser;
use App\Validators\StatsLoginValidator;

class TrackStatsUser extends BaseStatsTrack
{
    /**
     * Execute the job.
     */
    public function handle(): void
    {
        parent::handle();

        $addLogin = $this->input['add_login'] ?? false;
        unset($this->input['add_login']);

        StatsUser::updateOrCreate(['id' => $this->input['id']], $this->input);

        if ($addLogin) {
            TrackStatsLogin::dispatch(app(StatsLoginValidator::class)->validate([
                'stats_user_id' => $this->input['id'],
                'time' => $this->input['time'],
                'ip' => $this->input['ip'],
                'user_agent' => $this->input['user_agent'],
            ]));
        }
    }
}
