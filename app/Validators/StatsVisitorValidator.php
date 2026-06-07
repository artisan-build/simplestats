<?php

namespace App\Validators;

use App\Concerns\HasStatsTrackingValidation;

class StatsVisitorValidator extends BaseStatsValidator
{
    use HasStatsTrackingValidation;

    public function rules(): array
    {
        return array_merge(parent::rules(), $this->trackingValidation, $this->propertyRules(), [
            'visitor_hash' => ['nullable', 'string'],
        ]);
    }
}
