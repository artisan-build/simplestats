<?php

namespace App\Validators;

class StatsUserValidator extends BaseStatsValidator
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'id' => ['required', 'integer'],
            'ip' => ['nullable', 'ip'],
            'user_agent' => ['nullable', 'string'],
            'track_country' => ['nullable', 'string'],
            'track_source' => ['nullable', 'string'],
            'track_medium' => ['nullable', 'string'],
            'track_campaign' => ['nullable', 'string'],
            'track_term' => ['nullable', 'string'],
            'track_content' => ['nullable', 'string'],
        ]);
    }
}
