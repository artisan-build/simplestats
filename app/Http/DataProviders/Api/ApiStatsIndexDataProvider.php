<?php

namespace App\Http\DataProviders\Api;

use App\Http\Requests\Api\ApiStatsFilterRequest;
use App\Services\StatsDetailsService;
use App\Services\StatsService;

class ApiStatsIndexDataProvider extends AbstractApiStatsDataProvider
{
    public function __construct(
        ApiStatsFilterRequest $request,
        private readonly StatsDetailsService $statsDetailsService,
    ) {
        parent::__construct($request);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $filters = $this->filters();

        $response = [
            'data' => array_values($this->statsDetailsService->getStats($filters)),
            'meta' => $this->meta($filters),
        ];

        if ($filters->shouldCompare()) {
            $response['data_previous'] = array_values($this->statsDetailsService->getStats(
                StatsService::createPrevFiltersForComparison($filters),
            ));
        }

        return $response;
    }
}
