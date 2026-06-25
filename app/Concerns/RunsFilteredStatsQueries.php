<?php

namespace App\Concerns;

use Illuminate\Support\Facades\DB;

trait RunsFilteredStatsQueries
{
    /**
     * @param  array<string, mixed>  $filterConditions
     * @return array<string, mixed>
     */
    protected function formatFilter(array $filterConditions): array
    {
        return collect($filterConditions)->mapWithKeys(function ($value, $key) {
            return [':'.$key => $value];
        })->toArray();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function getDataArray(string $sql): array
    {
        return json_decode(json_encode(DB::select($sql)), true);
    }
}
