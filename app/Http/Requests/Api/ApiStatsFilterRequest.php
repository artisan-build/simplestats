<?php

namespace App\Http\Requests\Api;

use App\Enums\StatsRangePreset;
use App\Http\Requests\StatsFilterRequest;
use Illuminate\Validation\Rule;

class ApiStatsFilterRequest extends StatsFilterRequest
{
    protected function prepareForValidation(): void
    {
        // No project auto-detection needed for API requests.
        // The project is the authenticated user (Sanctum token).
    }

    public function rules(): array
    {
        return [
            ...$this->sharedFilterRules(),
            'preset' => [
                'sometimes',
                'string',
                Rule::enum(StatsRangePreset::class),
            ],
            'limit' => [
                'sometimes',
                'integer',
                'min:1',
                'max:100',
            ],
        ];
    }
}
