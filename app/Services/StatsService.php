<?php

namespace App\Services;

use App\DTOs\StatsFilterDto;
use App\Models\BaseStat;
use App\Models\Instance;
use App\Models\Project;
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
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class StatsService
{
    const NAU_LOGIN_COUNTER = 2;

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
            ['model' => StatsDeviceBrowser::class, 'type' => 'device_browser', 'title' => 'Browser', 'icon' => 'path'],
            ['model' => StatsDevicePlatform::class, 'type' => 'device_platform', 'title' => 'OS'],
            ['model' => StatsDeviceType::class, 'type' => 'device_type', 'title' => 'Size', 'icon' => 'path'],
        ],
    ];

    public static function getStatsModels(): Collection
    {
        return collect(self::$statsModels);
    }

    public static function getStatsTypes(): Collection
    {
        return collect(self::$statsTypes);
    }

    public static function getStatsTypesCamel(array $skip = []): Collection
    {
        return collect(self::$statsTypes)->map(function ($types) use ($skip) {
            return collect($types)->map(function ($item) use ($skip) {
                if (!empty($skip)) foreach ($skip as $key) {unset($item[$key]);}

                $item['type'] = str($item['type'])->camel()->toString();
                return $item;
            });
        });
    }

    public static function getStatsTypesCamelFlat(): Collection
    {
        return collect(self::getStatsTypesCamel())->flatMap(function ($item) {
            return $item->pluck('type');
        });
    }

    public static function getFilterStatsTypeNames(StatsFilterDto $filters): Collection
    {
        return collect(self::getStatsTypesCamel())->flatMap(function ($item) {
            return collect($item)->pluck('model', 'type');
        })->mapWithKeys(function ($model, $type) use ($filters) {
            if (!empty($filters->$type)) {
                return [$type => $model::find($filters->$type)?->name ?? 'Direct / Unknown'];
            }

            return [];
        });
    }

    public static function formatFilters(StatsFilterDto $filters): array
    {
        $formatted = collect((array) $filters)->merge([
            'rangeStart' => $filters->rangeStart->format('Y-m-d'),
        ])->filter()->toArray(); //remove 0 values

        // project and instance must have a default value...
        if (!isset($formatted['projectId'])) $formatted['projectId'] = 0;
        if (!isset($formatted['instanceId'])) $formatted['instanceId'] = 0;

        return $formatted;
    }

    public function safeDivision($numerator, $denominator): float|int
    {
        return $denominator == 0 ? 0 : $numerator / $denominator;
    }

    public function getStatsStartingAt(Team|Project|Instance $model, bool $recalc = false)
    {
        if ($model->stats_starting_at && ! $recalc) {
            return $model->stats_starting_at;
        }

        $key = str($model->getTable())->singular().'_'.$model->getKeyName();

        $start = collect(self::getStatsModels())->map(
            fn($statsModel) => $statsModel::withoutGlobalScope('instance')->where([$key => $model->id])->min('time')
        )->filter()->min();

        $model->update(['stats_starting_at' => $start]);

        return $start;
    }

    /**
     * Compatible with a command (no auth so all teams will be handled).
     * Else if auth exists, only the auth users team will be handled...
     */
    public function recalcStatsStartingAt(): void
    {
        Team::with('owner')->get()->each(function(Team $team) {
            if (empty($team->owner)) return;
            $this->getStatsStartingAt($team, true);
        });

        Project::query()->get()->each(function(Project $project) {
            $this->getStatsStartingAt($project, true);
        });

        Instance::query()->get()->each(function(Instance $instance) {
            $this->getStatsStartingAt($instance, true);
        });
    }
}
