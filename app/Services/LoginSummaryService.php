<?php

namespace App\Services;

use App\DTOs\StatsFilterDto;
use App\Enums\StatsRangeType;
use App\Models\Project;
use App\Models\User;

class LoginSummaryService
{
    const MIN_HOURS_SINCE_PREVIOUS_LOGIN = 12;

    const HOUR_RANGE_MAX_HOURS = 48;

    const SESSION_KEY = 'loginSummaryPending';

    public function __construct(
        private StatsDetailsService $statsDetailsService,
    ) {}

    public function isEligible(User $user): bool
    {
        return session()->has(self::SESSION_KEY)
            && ! app('impersonate')->isImpersonating()
            && ($user->settings['loginSummaryEnabled'] ?? true)
            && $user->current_team_id
            && $user->previous_login_at
            && $user->previous_login_at->lte(now()->subHours(self::MIN_HOURS_SINCE_PREVIOUS_LOGIN));
    }

    public function consumePending(): void
    {
        session()->pull(self::SESSION_KEY);
    }

    /**
     * @return array{since: string, metrics: array<string, int|float>}|null
     */
    public function getSummary(User $user): ?array
    {
        $rows = collect($this->statsDetailsService->getRaw($this->getFilter($user)));
        $team = $user->currentTeam;

        $metrics = collect([
            'net' => $team->tracksUsage(Project::USAGE_PAYMENTS) ? round($rows->sum('pd_net'), 2) : null,
            'reg' => $team->tracksUsage(Project::USAGE_USERS) ? (int) $rows->sum('pd_reg') : null,
            'visitors' => $team->tracksUsage(Project::USAGE_VISITORS) ? (int) $rows->sum('pd_visitor') : null,
        ])->filter(fn ($value) => $value > 0);

        if ($metrics->isEmpty()) {
            return null;
        }

        return [
            'since' => $user->previous_login_at->toIso8601String(),
            'metrics' => $metrics->toArray(),
        ];
    }

    private function getFilter(User $user): StatsFilterDto
    {
        $hours = (int) ceil($user->previous_login_at->diffInHours(now(), absolute: true));

        [$rangeType, $rangeAmount] = $hours <= self::HOUR_RANGE_MAX_HOURS
            ? [StatsRangeType::HOUR, max(1, $hours)]
            : [StatsRangeType::DAY, max(1, (int) ceil($hours / 24))];

        return new StatsFilterDto(
            projectId: StatsFilterDto::TRACK_ALL,
            rangeAmount: $rangeAmount,
            rangeType: $rangeType,
            rangeStart: now(),
            comparison: '0',
        );
    }
}
