<?php

namespace App\Http\DataProviders;

use App\DTOs\StatsFilterDto;
use App\Http\Requests\StatsFilterRequest;
use App\Services\StatsDetailsService;
use App\Services\StatsService;

class StatsGroupsDataProvider extends AbstractDataProvider
{
    public function __construct(
        private StatsFilterRequest $request,
        private StatsDetailsService $statsDetailsService,
    ) {}

    public function toArray(): array
    {
        $this->rememberSelectedGroupType();

        $filters = StatsFilterDto::fromRequest($this->request);

        $response = [
            'data' => $this->fetchGroupedData($filters),
        ];

        if ($filters->shouldCompare()) {
            $response['data_previous'] = $this->fetchGroupedData(
                StatsService::createPrevFiltersForComparison($filters),
            );
        }

        return $response;
    }

    private function rememberSelectedGroupType(): void
    {
        if (! isset($this->request->group_type)) {
            return;
        }

        // the "property" group remembers the selected dimension (a property
        // name id), all other groups the selected stats type
        $this->request->user()->updateSettings(
            "selectedGroupTypes.{$this->request->project_id}.{$this->request->group_type}",
            $this->request->stats_type === 'property' ? (int) $this->request->property_name_id : $this->request->stats_type,
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fetchGroupedData(StatsFilterDto $filters): array
    {
        return $this->statsDetailsService->getGroupedData(
            $filters,
            $this->request->stats_type,
            $this->request->limit ?? 5, // must be equal to Load more in StatsGroupTable.vue
            $this->request->stats_sort,
            $this->request->property_name_id,
            $this->request->boolean('lifetime'),
        );
    }
}
