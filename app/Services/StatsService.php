<?php

namespace App\Services;

use App\DTOs\StatsFilterDto;
use App\Models\StatsDeviceBrowser;
use App\Models\StatsDevicePlatform;
use App\Models\StatsDeviceType;
use App\Models\StatsLocationCity;
use App\Models\StatsLocationCountry;
use App\Models\StatsLocationRegion;
use App\Models\StatsPageEntry;
use App\Models\StatsTrackCampaign;
use App\Models\StatsTrackContent;
use App\Models\StatsTrackMedium;
use App\Models\StatsTrackReferer;
use App\Models\StatsTrackSource;
use App\Models\StatsTrackTerm;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class StatsService
{
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

    const NAU_LOGIN_COUNTER = 2;

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
            'rangeStart' => $filters->rangeStart->format('Y-m-d')
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

    public function groupStats($stats): array
    {
        $result = ['stats' => []];
        $statsPrefixes = ['lt_' => 'lifetime', 'pd_' => 'per_date'];

        foreach ($stats as $key => $value) {
            if (Str::startsWith($key, array_keys($statsPrefixes))) {
                $parts = explode('_', $key);
                $statsKey = $statsPrefixes[$parts[0].'_'];
                $result['stats'][$statsKey][$parts[1]] = $value;
            } else {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    public function addStatsDetails(array $stats): array
    {
        // Lifetime
        if (isset($stats['lifetime'])) {
            $stats['lifetime']['FEE'] = $stats['lifetime']['GROSS'] - $stats['lifetime']['NET'];
            $stats['lifetime']['CR'] = round($this->safeDivision($stats['lifetime']['REG'], $stats['lifetime']['VISITOR']) * 100, 2);
            $stats['lifetime']['REG2DAU'] = round($this->safeDivision($stats['per_date']['DAU'], $stats['lifetime']['REG']) * 100, 2);
            $stats['lifetime']['REG2PU'] = round($this->safeDivision($stats['lifetime']['PU'], $stats['lifetime']['REG']) * 100, 2);
            $stats['lifetime']['REG2TAU'] = round($this->safeDivision($stats['lifetime']['TAU'], $stats['lifetime']['REG']) * 100, 2);
            $stats['lifetime']['ARPU'] = round($this->safeDivision($stats['lifetime']['NET'], $stats['lifetime']['REG']), 2);
            $stats['lifetime']['ARPPU'] = round($this->safeDivision($stats['lifetime']['NET'], $stats['lifetime']['PU']), 2);
        }

        return $stats;
    }

    public function calcStatsTotal(array $stats, array &$totalStats, array $skipCategories = []): void
    {
        foreach ($stats as $statCategory => $statsGroup) {
            if (in_array($statCategory, $skipCategories) || !is_array($statsGroup))
                continue;

            foreach ($statsGroup as $key => $value) {
                $totalStats[$statCategory][$key] = ($totalStats[$statCategory][$key] ?? 0) + $value;
            }
        }
    }
}
