<?php

namespace App\Validators;

class StatsCustomPropertyValidator extends BaseStatsValidator
{
    public function rules(): array
    {
        return array_merge(parent::rules(), $this->propertyRules(required: true), [
            'stats_user_id' => ['nullable', 'required_without:visitor_hash'],
            'visitor_hash' => ['nullable', 'string', 'required_without:stats_user_id'],
        ]);
    }
}
