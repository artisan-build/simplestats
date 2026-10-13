<?php

namespace App\Concerns;

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
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

trait HasStatsRelations
{
    public static function statsRelations(): array
    {
        return [
            'statsTrackReferer:id,name',
            'statsTrackSource:id,name',
            'statsTrackMedium:id,name',
            'statsTrackCampaign:id,name',
            'statsTrackTerm:id,name',
            'statsTrackContent:id,name',
            'statsPageEntry:id,name',
            'statsLocationCountry:id,name',
            'statsLocationRegion:id,name',
            'statsLocationCity:id,name',
            'statsDevicePlatform:id,name',
            'statsDeviceBrowser:id,name',
            'statsDeviceType:id,name',
        ];
    }

    protected function getStatsAttribute(): Collection
    {
        return collect(self::statsRelations())
            ->mapWithKeys(function ($relation): array {
                $key = explode(':', $relation)[0];
                $related = $this->{$key};

                return [$key => $related?->only(['id', 'name'])];
            })->filter();
    }

    /**
     * @return BelongsTo<StatsTrackReferer, $this>
     */
    public function statsTrackReferer(): BelongsTo
    {
        return $this->belongsTo(StatsTrackReferer::class);
    }

    /**
     * @return BelongsTo<StatsTrackSource, $this>
     */
    public function statsTrackSource(): BelongsTo
    {
        return $this->belongsTo(StatsTrackSource::class);
    }

    /**
     * @return BelongsTo<StatsTrackMedium, $this>
     */
    public function statsTrackMedium(): BelongsTo
    {
        return $this->belongsTo(StatsTrackMedium::class);
    }

    /**
     * @return BelongsTo<StatsTrackCampaign, $this>
     */
    public function statsTrackCampaign(): BelongsTo
    {
        return $this->belongsTo(StatsTrackCampaign::class);
    }

    /**
     * @return BelongsTo<StatsTrackTerm, $this>
     */
    public function statsTrackTerm(): BelongsTo
    {
        return $this->belongsTo(StatsTrackTerm::class);
    }

    /**
     * @return BelongsTo<StatsTrackContent, $this>
     */
    public function statsTrackContent(): BelongsTo
    {
        return $this->belongsTo(StatsTrackContent::class);
    }

    /**
     * @return BelongsTo<StatsPageEntry, $this>
     */
    public function statsPageEntry(): BelongsTo
    {
        return $this->belongsTo(StatsPageEntry::class);
    }

    /**
     * @return BelongsTo<StatsLocationCountry, $this>
     */
    public function statsLocationCountry(): BelongsTo
    {
        return $this->belongsTo(StatsLocationCountry::class);
    }

    /**
     * @return BelongsTo<StatsLocationRegion, $this>
     */
    public function statsLocationRegion(): BelongsTo
    {
        return $this->belongsTo(StatsLocationRegion::class);
    }

    /**
     * @return BelongsTo<StatsLocationCity, $this>
     */
    public function statsLocationCity(): BelongsTo
    {
        return $this->belongsTo(StatsLocationCity::class);
    }

    /**
     * @return BelongsTo<StatsDevicePlatform, $this>
     */
    public function statsDevicePlatform(): BelongsTo
    {
        return $this->belongsTo(StatsDevicePlatform::class);
    }

    /**
     * @return BelongsTo<StatsDeviceBrowser, $this>
     */
    public function statsDeviceBrowser(): BelongsTo
    {
        return $this->belongsTo(StatsDeviceBrowser::class);
    }

    /**
     * @return BelongsTo<StatsDeviceType, $this>
     */
    public function statsDeviceType(): BelongsTo
    {
        return $this->belongsTo(StatsDeviceType::class);
    }
}
