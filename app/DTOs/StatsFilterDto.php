<?php

namespace App\DTOs;

use App\Enums\StatsRangePreset;
use App\Enums\StatsRangeType;
use App\Http\Requests\StatsFilterRequest;
use App\Models\CustomStatsRangePreset;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Str;

class StatsFilterDto
{
    const TRACK_ALL = 0;

    const TRACK_ORGANIC = -1;

    public function __construct(
        public int $projectId,
        public int $rangeAmount,
        public StatsRangeType $rangeType,
        public CarbonInterface $rangeStart,
        public string $comparison,
        public ?StatsRangePreset $preset = null,
        public ?int $customPresetId = null,
        public int $trackReferer = 0,
        public int $trackSource = 0,
        public int $trackMedium = 0,
        public int $trackCampaign = 0,
        public int $trackTerm = 0,
        public int $trackContent = 0,
        public int $locationCountry = 0,
        public int $locationRegion = 0,
        public int $locationCity = 0,
        public int $deviceType = 0,
        public int $devicePlatform = 0,
        public int $deviceBrowser = 0,
        public int $pageEntry = 0,
        public int $customEventName = 0,
    ) {}

    public static function fromRequest(StatsFilterRequest $request, ?int $projectId = null): self
    {
        $customPresetId = null;
        $effectiveProjectId = $projectId ?? $request->project_id ?? 0;

        if ($request->preset && Str::startsWith($request->preset, 'custom_')) {
            $customPresetId = (int) Str::after($request->preset, 'custom_');
            $customPreset = CustomStatsRangePreset::findOrFail($customPresetId);
            $request->merge($customPreset->toRange());
        } elseif ($request->preset && $request->preset !== StatsRangePreset::CUSTOM->value) {
            $request->merge(StatsRangePreset::from($request->preset)->range($effectiveProjectId));
        } elseif (! $request->hasAny(['range_type', 'range_amount', 'range_start', 'preset']) && empty($_GET)) {
            $request->merge([
                ...StatsRangePreset::LAST_7_DAYS->range($effectiveProjectId),
                'preset' => StatsRangePreset::LAST_7_DAYS->value,
            ]);
        }

        $rangeType = StatsRangeType::from($request->range_type ?? StatsRangeType::DAY->value);
        $rangeAmount = $request->range_amount ?? $rangeType->defaultRangeAmount();
        $rangeStart = CarbonImmutable::parse($request->range_start ?? now());

        if ($rangeStart->isToday()) {
            $rangeStart = now();
        } else {
            $rangeStart = $rangeStart->endOfDay();
        }

        return new self(
            projectId: $effectiveProjectId,
            rangeAmount: $rangeAmount,
            rangeType: $rangeType,
            rangeStart: $rangeStart,
            comparison: $request->comparison ?? '0',
            preset: ($request->preset && ! Str::startsWith($request->preset, 'custom_'))
                ? StatsRangePreset::tryFrom($request->preset)
                : null,
            customPresetId: $customPresetId,
            trackReferer: $request->track_referer ?? 0,
            trackSource: $request->track_source ?? 0,
            trackMedium: $request->track_medium ?? 0,
            trackCampaign: $request->track_campaign ?? 0,
            trackTerm: $request->track_term ?? 0,
            trackContent: $request->track_content ?? 0,
            locationCountry: $request->location_country ?? 0,
            locationRegion: $request->location_region ?? 0,
            locationCity: $request->location_city ?? 0,
            deviceType: $request->device_type ?? 0,
            devicePlatform: $request->device_platform ?? 0,
            deviceBrowser: $request->device_browser ?? 0,
            pageEntry: $request->page_entry ?? 0,
            customEventName: $request->custom_event_name ?? 0,
        );
    }

    public function makeHash(): string
    {
        return md5(json_encode([
            $this->getTeamId(),
            $this->projectId,
            $this->rangeType,
            $this->trackReferer,
            $this->trackSource,
            $this->trackMedium,
            $this->trackCampaign,
            $this->trackTerm,
            $this->trackContent,
            $this->locationCountry,
            $this->locationRegion,
            $this->locationCity,
            $this->deviceType,
            $this->devicePlatform,
            $this->deviceBrowser,
            $this->pageEntry,
            $this->customEventName,
        ]));
    }

    public function getTeamId(): int
    {
        return Context::get('team_id');
    }
}
