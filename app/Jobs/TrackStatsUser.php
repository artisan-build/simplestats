<?php

namespace App\Jobs;

use App\Models\StatsUser;
use App\Validators\StatsLoginValidator;
use Carbon\CarbonImmutable;

class TrackStatsUser extends BaseStatsTrack
{
    /**
     * Execute the job.
     */
    public function handle(): void
    {
        parent::handle();

        $this->resolveVisitorHash();

        $addLogin = $this->input['add_login'] ?? false;
        unset($this->input['add_login']);

        $properties = $this->input['properties'] ?? [];
        unset($this->input['properties']);

        $statsUser = StatsUser::firstOrNew(['id' => $this->input['id']]);

        // a pre-existing registered row was already alerted; a new row or a placeholder shell
        // (registered = false) is the first real registration.
        $isFirstRegistration = ! ($statsUser->exists && $statsUser->registered);

        $statsUser->fill($this->input);
        $statsUser->registered = true;
        $statsUser->save();

        $statsUser->recordCustomProperties($properties, CarbonImmutable::parse($this->input['time']));

        if ($isFirstRegistration) {
            $statsUser->sendRegistrationAlerts();
        }

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
