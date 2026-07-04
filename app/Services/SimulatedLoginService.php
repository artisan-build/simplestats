<?php

namespace App\Services;

use App\Models\StatsLogin;
use Carbon\CarbonImmutable;

/**
 * Simulated Natural Login Behavior (SNLB): builds a realistic weekly login series
 * for imported users between their registration and their last known activity.
 */
class SimulatedLoginService
{
    // safety cap against garbage dates (e.g. epoch defaults); ~20 years of weekly logins
    public const MAX_LOGINS_PER_USER = 1040;

    /**
     * @return list<array<string, mixed>> insert-ready stats_logins attribute rows
     */
    public function buildLoginRows(
        string $statsUserId,
        int $teamId,
        int $projectId,
        CarbonImmutable $registeredAt,
        CarbonImmutable $lastKnownActivity,
        string $timezone,
    ): array {
        $lastKnownActivity = $lastKnownActivity->min(now());

        $counter = 1;
        $rows = [$this->buildRow($statsUserId, $teamId, $projectId, $registeredAt, $counter)];

        if ($lastKnownActivity->lte($registeredAt)) {
            return $rows;
        }

        // jitter in the team's timezone so logins land in plausible local waking hours
        $cursor = $registeredAt->setTimezone($timezone);
        $finalDay = $lastKnownActivity->setTimezone($timezone)->startOfDay();

        while (count($rows) < self::MAX_LOGINS_PER_USER - 1) {
            // gaps of 4-7 days keep the "at least once a week" promise without robotic spikes
            $cursor = $cursor->addDays(random_int(4, 7))
                ->setTime(random_int(7, 22), random_int(0, 59), random_int(0, 59));

            // stop before the final calendar day; the closing login below covers it
            if ($cursor->gte($finalDay)) {
                break;
            }

            $rows[] = $this->buildRow($statsUserId, $teamId, $projectId, $cursor, ++$counter);
        }

        $rows[] = $this->buildRow($statsUserId, $teamId, $projectId, $lastKnownActivity, ++$counter);

        return $rows;
    }

    /**
     * Instantiating the model (without saving) runs the BaseStat::time() mutator,
     * so both time and time_utc are filled despite the later bulk insert.
     *
     * @return array<string, mixed>
     */
    private function buildRow(string $statsUserId, int $teamId, int $projectId, CarbonImmutable $time, int $counter): array
    {
        return (new StatsLogin([
            'stats_user_id' => $statsUserId,
            'team_id' => $teamId,
            'project_id' => $projectId,
            'counter' => $counter,
            'time' => $time,
        ]))->getAttributes();
    }
}
