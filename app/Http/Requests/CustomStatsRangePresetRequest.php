<?php

namespace App\Http\Requests;

use App\Enums\StatsRangeType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CustomStatsRangePresetRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:50'],
            'range_amount' => ['required', 'integer', 'min:1', 'max:60'],
            'range_type' => [
                'required',
                'string',
                Rule::in([
                    StatsRangeType::DAY->value,
                    StatsRangeType::WEEK->value,
                    StatsRangeType::MONTH->value,
                    StatsRangeType::YEAR->value,
                ]),
            ],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
        ];
    }
}
