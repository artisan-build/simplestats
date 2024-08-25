<?php

namespace App\Validators;

use App\Concerns\HasStatsTrackingValidation;

class StatsUserValidator extends BaseStatsValidator
{
    use HasStatsTrackingValidation;

    public function rules(): array
    {
        return array_merge(parent::rules(), $this->trackingValidation, [
            'id' => ['required', 'integer'],
            'add_login' => ['sometimes', 'bool'],
        ]);
    }
}
