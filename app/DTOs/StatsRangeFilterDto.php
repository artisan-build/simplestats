<?php

namespace App\DTOs;

use App\Enums\StatsRangeType;
use App\Http\Requests\DashboardRequest;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;

readonly class StatsRangeFilterDto
{
    const TRACK_ALL = 0;
    const TRACK_ORGANIC = -1;

    public function __construct(
        public int $projectId,
        public int $instanceId,
        public int $rangeAmount,
        public StatsRangeType $rangeType,
        public CarbonImmutable $rangeStart,
        public int $trackCountry = 0,
        public int $trackSource = 0,
        public int $trackMedium = 0,
        public int $trackCampaign = 0,
        public int $trackTerm = 0,
        public int $trackContent = 0,
        public bool $extendedTracking = false,
    ) {}

    public static function fromRequest(DashboardRequest $request): StatsRangeFilterDto
    {
        $rangeType = StatsRangeType::from(auth()->user()->selected_range_type->value ?? StatsRangeType::DAY->value);
        $rangeAmount = $request->rangeAmount ?? $rangeType->defaultRangeAmount();
        $rangeStart = Date::parse($request->rangeStart ?? now());

        return new self(
            projectId: $request->projectId ?? 0,
            instanceId: $request->instanceId ?? 0,
            rangeAmount: $rangeAmount,
            rangeType: $rangeType,
            rangeStart: $rangeStart,
            trackSource: $request->trackSource ?? 0,
            trackCountry: $request->trackCountry ?? 0,
            trackMedium: $request->trackMedium ?? 0,
            trackCampaign: $request->trackCampaign ?? 0,
            trackTerm: $request->trackTerm ?? 0,
            trackContent: $request->trackContent ?? 0,
            extendedTracking: $request->extendedTracking ?? false,
        );
    }

    public function makeHash(): string
    {
        return md5(json_encode([
            $this->getTeamId(),
            $this->projectId,
            $this->instanceId,
            $this->rangeType,
            $this->trackCountry,
            $this->trackSource,
            $this->trackMedium,
            $this->trackCampaign,
            $this->trackTerm,
            $this->trackContent,
        ]));
    }

    public function getTeamId()
    {
        return auth()->user()->currentTeam->id;
    }
}
