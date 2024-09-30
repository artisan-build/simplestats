<?php

namespace App\Validators;

class StatsLoginValidator extends BaseStatsValidator
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'stats_user_id' => ['required'],
            'ip' => ['nullable', 'ip'],
            'user_agent' => ['nullable', 'string'],
        ]);
    }
}
