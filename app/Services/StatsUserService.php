<?php

namespace App\Services;

use App\DTOs\StatsFilterDto;
use App\Models\StatsLogin;
use App\Models\StatsPayment;
use App\Models\StatsUser;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class StatsUserService
{
    /**
     * Sortable power-user columns, keyed by the token the frontend sends as
     * ?sort=. Only these keys are honored; anything else falls back to the
     * default lifetime-value ranking.
     */
    public const POWER_USER_SORTS = [
        'registered' => 'stats_users.time',
        'logins' => 'logins_count',
        'last_login' => 'last_login_at',
        'plan' => 'current_plan',
        'revenue' => 'total_gross',
    ];

    /**
     * The registered users of the selected scope ranked by lifetime value:
     * total gross revenue first, then login activity. All correlated subqueries
     * join on both stats_user_id AND project_id because the external user id is
     * only unique per project, not globally.
     */
    public function powerUsers(StatsFilterDto $filters, ?string $sort = null, string $direction = 'desc', int $perPage = 15): LengthAwarePaginator
    {
        $sortColumn = self::POWER_USER_SORTS[$sort] ?? null;
        $direction = $direction === 'asc' ? 'asc' : 'desc';

        return StatsUser::query()
            ->where('registered', true)
            ->when(
                $filters->projectId !== StatsFilterDto::TRACK_ALL,
                fn ($query) => $query->where('project_id', $filters->projectId),
            )
            ->with([
                'project:id,name,domain,impersonate_base_url',
                'statsLocationCountry:id,name',
                'statsDeviceType:id,name',
                'statsDevicePlatform:id,name',
                'statsDeviceBrowser:id,name',
                'statsTrackReferer:id,name',
            ])
            ->addSelect('stats_users.*')
            ->addSelect([
                'total_gross' => StatsPayment::query()
                    ->selectRaw('COALESCE(SUM(gross), 0)')
                    ->whereColumn('stats_payments.stats_user_id', 'stats_users.id')
                    ->whereColumn('stats_payments.project_id', 'stats_users.project_id'),
                'logins_count' => StatsLogin::query()
                    ->selectRaw('COUNT(*)')
                    ->whereColumn('stats_logins.stats_user_id', 'stats_users.id')
                    ->whereColumn('stats_logins.project_id', 'stats_users.project_id'),
                'last_login_at' => StatsLogin::query()
                    ->selectRaw('MAX(time)')
                    ->whereColumn('stats_logins.stats_user_id', 'stats_users.id')
                    ->whereColumn('stats_logins.project_id', 'stats_users.project_id'),
                'current_plan' => StatsPayment::query()
                    ->select('subscription_plan')
                    ->whereColumn('stats_payments.stats_user_id', 'stats_users.id')
                    ->whereColumn('stats_payments.project_id', 'stats_users.project_id')
                    ->whereNotNull('subscription_interval')
                    ->orderByDesc('time')
                    ->limit(1),
            ])
            ->withCasts([
                'total_gross' => 'float',
                'logins_count' => 'integer',
                'last_login_at' => 'datetime',
            ])
            ->when(
                $sortColumn !== null,
                // NULLS LAST so users without a login/plan/payment always sink to the
                // bottom instead of topping the list (Postgres defaults to NULLS FIRST
                // on DESC). $sortColumn and $direction are both whitelisted, so raw is safe.
                fn ($query) => $query->orderByRaw("{$sortColumn} {$direction} NULLS LAST"),
                fn ($query) => $query->orderByDesc('total_gross')->orderByDesc('logins_count'),
            )
            // stable tie-breaker so paginated pages never drop or duplicate rows
            ->orderBy('stats_users.id')
            ->paginate($perPage)
            ->withQueryString();
    }
}
