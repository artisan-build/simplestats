<?php

namespace App\Validators;

class StatsCustomEventValidator extends BaseStatsValidator
{
    public function prepareForValidation(array $input): array
    {
        if (! isset($input['time'])) {
            return $input;
        }

        return parent::prepareForValidation($input);
    }

    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'id' => ['required'],
            'name' => ['required', 'string', 'max:255'],
            'stats_user_id' => ['nullable'],
            'visitor_hash' => ['nullable'],
            'value' => ['nullable', 'numeric'],
        ]);
    }
}
