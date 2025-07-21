<?php

namespace App\DTOs;

use App\Enums\StatsRangeType;
use App\Http\Requests\StatsFilterRequest;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

class StatsFilterDto
{
    const TRACK_ALL = 0;
    const TRACK_ORGANIC = -1;

    public function __construct(
        public int $projectId,
        public int $instanceId,
        public int $rangeAmount,
        public StatsRangeType $rangeType,
        public CarbonInterface $rangeStart,
        public string $comparison,
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
    ) {}

    public static function fromRequest(StatsFilterRequest $request): StatsFilterDto
    {
        $rangeType = StatsRangeType::from($request->rangeType ?? StatsRangeType::DAY->value);
        $rangeAmount = $request->rangeAmount ?? $rangeType->defaultRangeAmount();
        $rangeStart = CarbonImmutable::parse($request->rangeStart ?? now());

        if ($rangeStart->isToday()) {
            $rangeStart = now();
        } else {
            $rangeStart = $rangeStart->endOfDay();
        }

        return new self(
            projectId: $request->projectId ?? 0,
            instanceId: $request->instanceId ?? 0,
            rangeAmount: $rangeAmount,
            rangeType: $rangeType,
            rangeStart: $rangeStart,
            comparison: $request->comparison ?? '0',
            trackReferer: $request->trackReferer ?? 0,
            trackSource: $request->trackSource ?? 0,
            trackMedium: $request->trackMedium ?? 0,
            trackCampaign: $request->trackCampaign ?? 0,
            trackTerm: $request->trackTerm ?? 0,
            trackContent: $request->trackContent ?? 0,
            locationCountry: $request->locationCountry ?? 0,
            locationRegion: $request->locationRegion ?? 0,
            locationCity: $request->locationCity ?? 0,
            deviceType: $request->deviceType ?? 0,
            devicePlatform: $request->devicePlatform ?? 0,
            deviceBrowser: $request->deviceBrowser ?? 0,
            pageEntry: $request->pageEntry ?? 0,
        );
    }

    public function makeHash(): string
    {
        return md5(json_encode([
            $this->getTeamId(),
            $this->projectId,
            $this->instanceId,
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
        ]));
    }

    public function getTeamId()
    {
        return auth()->user()->team->id;
    }
}
