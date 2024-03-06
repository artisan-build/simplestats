<?php

namespace App\Validators;

class StatsUserValidator extends BaseValidator
{
    public function rules(): array
    {
        return [
            'id' => ['required', 'integer'],
            'track_source' => ['nullable', 'string'],
            'track_medium' => ['nullable', 'string'],
            'track_campaign' => ['nullable', 'string'],
            'track_term' => ['nullable', 'string'],
            'track_content' => ['nullable', 'string'],
            'time' => ['required', 'date_format:Y-m-d'],
        ];
    }
}
