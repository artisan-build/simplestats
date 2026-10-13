<?php

namespace App\Jobs;

use App\Models\StatsUser;
use App\Validators\StatsLoginValidator;
use Carbon\CarbonImmutable;
use Illuminate\Database\UniqueConstraintViolationException;

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

        // retried once because a parallel ingest worker (duplicate stats-user call or a
        // login/payment placeholder shell) can win the insert between firstOrNew and save;
        // the second attempt then finds the winner's row and takes the update path
        [$statsUser, $isFirstRegistration] = retry(2, function (): array {
            $statsUser = StatsUser::firstOrNew(['id' => $this->input['id']]);

            // a pre-existing registered row was already alerted; a new row or a placeholder shell
            // (registered = false) is the first real registration.
            $isFirstRegistration = ! $statsUser->exists || ! $statsUser->registered;

            $statsUser->fill($this->input);
            $statsUser->registered = true;

            // savepoint wrapper (same as the framework's createOrFirst): without it, a unique
            // violation inside an outer transaction (e.g. RefreshDatabase) aborts the whole
            // Postgres transaction and the retry could not run
            $statsUser->newModelQuery()->withSavepointIfNeeded(fn () => $statsUser->save());

            return [$statsUser, $isFirstRegistration];
        }, 0, fn ($e): bool => $e instanceof UniqueConstraintViolationException);

        $statsUser->recordCustomProperties($properties, CarbonImmutable::parse($this->input['time']));

        if ($isFirstRegistration) {
            $statsUser->sendRegistrationAlerts();
        }

        if ($addLogin) {
            dispatch(new TrackStatsLogin(resolve(StatsLoginValidator::class)->validate([
                'stats_user_id' => $this->input['id'],
                'time' => $this->input['time'],
                'ip' => $this->input['ip'],
                'user_agent' => $this->input['user_agent'],
            ])));
        }
    }
}
