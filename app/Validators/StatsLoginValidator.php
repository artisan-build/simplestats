<?php

namespace App\Validators;

class StatsLoginValidator extends BaseValidator
{
    public function rules(): array
    {
        return [
            'stats_user_id' => ['required', 'integer', 'exists:stats_users,id'],
            'time' => ['required', 'date'],
        ];
    }
}
