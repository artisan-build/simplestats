<?php

namespace App\Http\DataProviders\Api;

use App\DTOs\StatsFilterDto;
use App\Enums\StatsRangePreset;
use App\Enums\StatsRangeType;
use App\Http\DataProviders\AbstractDataProvider;
use App\Http\Requests\Api\ApiStatsFilterRequest;
use Carbon\CarbonInterface;
use Illuminate\Support\Str;

abstract class AbstractApiStatsDataProvider extends AbstractDataProvider
{
    public function __construct(
        protected readonly ApiStatsFilterRequest $request,
    ) {}

    protected function filters(): StatsFilterDto
    {
        return StatsFilterDto::fromRequest($this->request, $this->request->user()->id);
    }

    /**
     * @return array<string, mixed>
     */
    protected function meta(StatsFilterDto $filters): array
    {
        return collect((array) $filters)
            ->except('customPresetId')
            ->mapWithKeys(fn ($value, $key): array => [Str::snake($key) => match (true) {
                $value instanceof StatsRangeType => $value->value,
                $value instanceof CarbonInterface => $value->format('Y-m-d'),
                $value instanceof StatsRangePreset => $value->value,
                default => $value,
            }])
            ->toArray();
    }
}
