<?php

namespace App\Services;

use App\DTOs\StatsFilterDto;
use App\Enums\ComparisonType;
use App\Models\Campaign;
use App\Models\Project;
use App\Models\StatsCustomEventName;
use App\Models\StatsCustomPropertyValue;
use App\Models\StatsDeviceBrowser;
use App\Models\StatsDevicePlatform;
use App\Models\StatsDeviceType;
use App\Models\StatsLocationCity;
use App\Models\StatsLocationCountry;
use App\Models\StatsLocationRegion;
use App\Models\StatsLogin;
use App\Models\StatsPageEntry;
use App\Models\StatsPayment;
use App\Models\StatsTrackCampaign;
use App\Models\StatsTrackContent;
use App\Models\StatsTrackMedium;
use App\Models\StatsTrackReferer;
use App\Models\StatsTrackSource;
use App\Models\StatsTrackTerm;
use App\Models\StatsUser;
use App\Models\StatsVisitor;
use App\Models\Team;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class StatsService
{
    const NAU_LOGIN_COUNTER = 2;

    // trailing window (in days) for the "active paying user" metrics (pd_daapu):
    // a user counts as actively paying on a login day if a payment falls within
    // the preceding DAAPU_WINDOW_DAYS. Referenced by both the aggregation SQL and
    // the incremental aggregation guard in StatsIncrementService.
    const DAAPU_WINDOW_DAYS = 20;

    // grace window (in days) added to a subscription's interval before it counts
    // as churned in the MRR snapshot, so a renewal that lands a few days late is
    // not briefly dropped from MRR. Referenced by MrrAggregationService::computeMrrAtDate().
    const MRR_ACTIVE_GRACE_DAYS = 3;

    const TRACKING_USAGE_BITS_VISITORS = 1;

    const TRACKING_USAGE_BITS_USERS = 2;

    const TRACKING_USAGE_BITS_PAYMENTS = 4;

    protected static array $statsModels = [
        StatsVisitor::class,
        StatsUser::class,
        StatsLogin::class,
        StatsPayment::class,
    ];

    protected static array $statsTypes = [
        'track' => [
            ['model' => StatsTrackReferer::class, 'type' => 'track_referer', 'title' => 'Referrer', 'icon' => 'favicon'],
            ['model' => StatsTrackSource::class, 'type' => 'track_source', 'title' => 'Source'],
            ['model' => StatsTrackMedium::class, 'type' => 'track_medium', 'title' => 'Medium'],
            ['model' => StatsTrackCampaign::class, 'type' => 'track_campaign', 'title' => 'Campaign'],
            ['model' => StatsTrackTerm::class, 'type' => 'track_term', 'title' => 'Term'],
            ['model' => StatsTrackContent::class, 'type' => 'track_content', 'title' => 'Content'],
        ],
        'page' => [
            ['model' => StatsPageEntry::class, 'type' => 'page_entry', 'title' => 'Entry Pages'],
        ],
        'location' => [
            ['model' => StatsLocationCountry::class, 'type' => 'location_country', 'title' => 'Countries', 'icon' => 'country'],
            ['model' => StatsLocationRegion::class, 'type' => 'location_region', 'title' => 'Regions', 'icon' => 'country'],
            ['model' => StatsLocationCity::class, 'type' => 'location_city', 'title' => 'Cities', 'icon' => 'country'],
        ],
        'device' => [
            ['model' => StatsDevicePlatform::class, 'type' => 'device_platform', 'title' => 'Platform', 'icon' => 'path'],
            ['model' => StatsDeviceBrowser::class, 'type' => 'device_browser', 'title' => 'Browser', 'icon' => 'path'],
            ['model' => StatsDeviceType::class, 'type' => 'device_type', 'title' => 'Size', 'icon' => 'path'],
        ],
        'event' => [
            ['model' => StatsCustomEventName::class, 'type' => 'custom_event_name', 'title' => 'Events'],
        ],
    ];

    /**
     * @throws Exception
     */
    public static function createPrevFiltersForComparison(StatsFilterDto $filters): StatsFilterDto
    {
        $prevFilters = clone $filters;

        $prevFilters->rangeStart = match ($filters->comparison) {
            ComparisonType::PERIOD->value => $prevFilters->rangeType->getPrevStartForPeriod($prevFilters->rangeStart, $prevFilters->rangeAmount),
            ComparisonType::CYCLE->value => $prevFilters->rangeType->getPrevStartForCycle($prevFilters->rangeStart),
            ComparisonType::YEAR->value => $prevFilters->rangeStart->copy()->subYear(),
            default => $prevFilters->rangeStart,
        };

        return $prevFilters;
    }

    public static function getStatsModels(): Collection
    {
        return collect(self::$statsModels);
    }

    public static function getStatsTypes(): Collection
    {
        return collect(self::$statsTypes);
    }

    public static function getStatsTypesFormatted(array $skip = []): Collection
    {
        return collect(self::$statsTypes)->map(function ($types) use ($skip) {
            return collect($types)->map(function ($item) use ($skip) {
                if (! empty($skip)) {
                    foreach ($skip as $key) {
                        unset($item[$key]);
                    }
                }

                return $item;
            });
        });
    }

    public static function getStatsTypesFlatMap(array $skip = []): Collection
    {
        return collect(self::getStatsTypesFormatted($skip))->flatMap(fn ($group) => $group);
    }

    public static function asOptions(array $skip = []): Collection
    {
        return collect(self::getStatsTypesFlatMap($skip))->map(function ($type) {
            return [
                'id' => $type['model'],
                'name' => ucfirst($type['title']),
            ];
        })->values();
    }

    public static function getStatsTypesFlat(): Collection
    {
        return collect(self::getStatsTypesFormatted())->flatMap(function ($item) {
            return $item->pluck('type');
        });
    }

    public static function getFilterStatsTypeNames(StatsFilterDto $filters): Collection
    {
        $names = collect(self::getStatsTypesFormatted())->flatMap(function ($item) {
            return collect($item)->pluck('model', 'type');
        })->mapWithKeys(function ($model, $type) use ($filters) {
            $camelType = Str::camel($type);
            if (! empty($filters->$camelType)) {
                return [$type => $model::find($filters->$camelType)?->name ?? 'Direct / Unknown'];
            }

            return [];
        });

        // Custom property filter chip: value plus its (humanised) property name.
        if (! empty($filters->propertyValue)) {
            $propertyValue = StatsCustomPropertyValue::with('name')->find($filters->propertyValue);

            if ($propertyValue !== null) {
                $names = $names->merge([
                    'property_value' => $propertyValue->value,
                    'property_value_title' => Str::title(str_replace('_', ' ', $propertyValue->name->name)),
                ]);
            }
        }

        if (! empty($filters->campaign)) {
            $campaign = Campaign::find($filters->campaign);

            if ($campaign !== null) {
                $names = $names->merge(['campaign' => $campaign->name]);
            }
        }

        return $names;
    }

    public static function formatFilters(StatsFilterDto $filters): array
    {
        $formatted = collect((array) $filters)
            ->mapWithKeys(fn ($value, $key) => [Str::snake($key) => $value])
            ->merge(['range_start' => $filters->rangeStart->format('Y-m-d')])
            ->except('custom_preset_id')
            ->filter()
            ->toArray();

        if ($filters->customPresetId) {
            $formatted['preset'] = 'custom_'.$filters->customPresetId;
        }

        // project must have a default value...
        if (! isset($formatted['project_id'])) {
            $formatted['project_id'] = 0;
        }
        if (! isset($formatted['comparison'])) {
            $formatted['comparison'] = 0;
        }

        return $formatted;
    }

    public function safeDivision($numerator, $denominator): float|int
    {
        return $denominator == 0 ? 0 : $numerator / $denominator;
    }

    public function getStatsStartingAt(array $filter): Carbon
    {
        // filter to remove 0 key value pairs...
        $where = collect($filter)->filter()->toArray();

        return Carbon::parse(Project::where($where)->min('stats_starting_at'));
    }

    /**
     * Compatible with an artisan command (no auth available, so all teams will be handled).
     * Else if auth exists, only the auth users team will be handled...
     */
    public function recalculateStatsStartingAt(): void
    {
        Project::all()->each(function (Project $project) {
            $start = collect(StatsService::getStatsModels())->map(
                fn ($statsModel) => $statsModel::where(['project_id' => $project->id])->min('time')
            )->filter()->min();

            if ($start) {
                $project->update(['stats_starting_at' => $start]);
            }
        });
    }

    public static function getTrackingUsages(): Collection
    {
        return collect([
            'visitors' => [
                'bit' => self::TRACKING_USAGE_BITS_VISITORS,
                'name' => 'Visitors',
                'descr' => 'Track anonymous visitors with UTMs, location, devices, etc.',
            ],
            'users' => [
                'bit' => self::TRACKING_USAGE_BITS_USERS,
                'name' => 'Registrations',
                'descr' => 'Track user signups and logins, including timestamps.',
            ],
            'payments' => [
                'bit' => self::TRACKING_USAGE_BITS_PAYMENTS,
                'name' => 'Payments',
                'descr' => 'Track revenue and link it back to visitors or registrations.',
            ],
        ]);
    }

    public static function getTrackingUsageBitsCount()
    {
        return self::getTrackingUsages()->sum('bit');
    }

    public static function getComparisonTypes(): array
    {
        return collect(ComparisonType::cases())->map(function ($type) {
            return [
                'id' => $type->value,
                'name' => $type->getTitle(),
            ];
        })->toArray();
    }

    public static function showFreePlanWarning(User $user): bool
    {
        return $user->team->owner->sparkPlan()->name === SubscriptionService::PLAN_FREE
            && $user->created_at->diffInDays(now()) > 7
            && ! ($user->settings['hideFreePlanWarning'] ?? false);
    }

    public static function showReadDocsWarning(User $user): bool
    {
        return $user->currentTeam->events_count_overall <= 0
            && $user->created_at->diffInDays(now()) > 1
            && ! ($user->settings['hideReadDocsWarning'] ?? false);
    }
}
