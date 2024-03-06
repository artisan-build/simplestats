<?php

namespace App\Validators;

class StatsLoginValidator extends BaseValidator
{
    public function rules(): array
    {
        return [
            'stats_user_id' => ['required', 'integer'],
            'time' => ['required', 'date_format:Y-m-d'],
        ];
    }
}
