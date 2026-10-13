<?php

namespace App\Services;

use App\DTOs\StatsFilterDto;
use App\Enums\StatsRangeType;
use App\Models\Project;
use App\Models\User;

class LoginSummaryService
{
    // High on purpose: daily users know their numbers, the summary only has
    // value (and its wow effect) for someone returning after a real absence.
    const MIN_DAYS_SINCE_PREVIOUS_LOGIN = 3;

    public function __construct(
        private readonly StatsDetailsService $statsDetailsService,
    ) {}

    public function isEligible(User $user): bool
    {
        return $user->login_summary_pending
            && ! resolve('impersonate')->isImpersonating()
            && ($user->settings['loginSummaryEnabled'] ?? true)
            && $user->current_team_id
            && $user->previous_login_at
            && $user->previous_login_at->lte(now()->subDays(self::MIN_DAYS_SINCE_PREVIOUS_LOGIN));
    }

    /**
     * The pending state lives on the user, not in the session: the dashboard fires
     * several requests in parallel, and a slower one writing back its stale session
     * copy resurrected the consumed session flag, so the modal reappeared.
     */
    public function consumePending(User $user): void
    {
        $user->update(['login_summary_pending' => false]);
    }

    /**
     * @return array{since: string, metrics: array<string, int|float>}|null
     */
    public function getSummary(User $user): ?array
    {
        $rows = collect($this->statsDetailsService->getRaw($this->getFilter($user)));
        $team = $user->currentTeam;

        $tracked = collect([
            'net' => $team->tracksUsage(Project::USAGE_PAYMENTS) ? round($rows->sum('pd_net'), 2) : null,
            'reg' => $team->tracksUsage(Project::USAGE_USERS) ? (int) $rows->sum('pd_reg') : null,
            'visitors' => $team->tracksUsage(Project::USAGE_VISITORS) ? (int) $rows->sum('pd_visitor') : null,
        ])->reject(fn ($value): bool => $value === null);

        $metrics = $tracked->filter(fn ($value): bool => $value > 0);

        // A lone non-zero metric (usually just visitors) is not worth a modal,
        // unless it is the only metric the team tracks at all.
        if ($metrics->isEmpty() || $metrics->count() < min(2, $tracked->count())) {
            return null;
        }

        return [
            'since' => $user->previous_login_at->toIso8601String(),
            'metrics' => $metrics->all(),
        ];
    }

    private function getFilter(User $user): StatsFilterDto
    {
        $days = (int) ceil($user->previous_login_at->diffInDays(now(), absolute: true));

        return new StatsFilterDto(
            projectId: StatsFilterDto::TRACK_ALL,
            rangeAmount: max(1, $days),
            rangeType: StatsRangeType::DAY,
            rangeStart: now(),
            comparison: '0',
        );
    }
}
