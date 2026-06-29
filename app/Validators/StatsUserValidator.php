<?php

namespace App\Validators;

use App\Concerns\HasStatsTrackingValidation;

class StatsUserValidator extends BaseStatsValidator
{
    use HasStatsTrackingValidation;

    public function rules(): array
    {
        return array_merge(parent::rules(), $this->trackingValidation, $this->propertyRules(), [
            'id' => ['required'],
            'add_login' => ['sometimes', 'bool'],
            'visitor_hash' => ['nullable', 'string'],
        ]);
    }
}
