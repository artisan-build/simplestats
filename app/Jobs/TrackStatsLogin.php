<?php

namespace App\Jobs;

use App\Models\StatsLogin;
use App\Services\StatsService;
use Carbon\Carbon;

class TrackStatsLogin extends BaseStatsTrack
{
    /**
     * Execute the job.
     */
    public function handle(): void
    {
        parent::handle();

        // on logins, we do not do an updateOrCreate, cause the client not sends ids, which makes not real sense for logins.
        // also only to the second login (to check NAU after reg. on the same day) we save logins on the same day,
        // else we only save one login per day. We do firstOrCreate here, do avoid duplicates.
        if (! StatsLogin::where([
            'stats_user_id' => $this->input['stats_user_id'],
            'counter' => StatsService::NAU_LOGIN_COUNTER,
        ])->exists()) {
            StatsLogin::create($this->input);
        } elseif (! StatsLogin::where('stats_user_id', $this->input['stats_user_id'])
            ->whereDate('time', Carbon::parse($this->input['time'])->format('Y-m-d'))->first()) {
            StatsLogin::create($this->input);
        }
    }
}
