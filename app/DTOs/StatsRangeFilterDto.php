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
        public int $trackSource = 0,
        public int $trackMedium = 0,
        public int $trackCampaign = 0,
        public int $trackTerm = 0,
        public int $trackContent = 0,
    ) {}

    public static function fromRequest(DashboardRequest $request): StatsRangeFilterDto
    {
        return new self(
            $request->projectId ?? 0,
            $request->instanceId ?? 0,
            $request->rangeAmount ?? 7,
            StatsRangeType::from($request->rangeType ?? StatsRangeType::DAY->value),
            Date::parse($request->rangeStart ?? now()),
            $request->trackSource ?? 0,
            $request->trackMedium ?? 0,
            $request->trackCampaign ?? 0,
            $request->trackTerm ?? 0,
            $request->trackContent ?? 0,
        );
    }
}
