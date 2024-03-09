<?php

namespace App\Validators;

use Illuminate\Support\Facades\Validator;

class BaseStatsValidator
{
    public function prepareForValidation(array $input): array
    {
        return $input;
    }

    public function rules(): array
    {
        // we do not allow logging in the future
        return [
            'time' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
        ];
    }

    public function validate($input): void
    {
        Validator::make(
            $this->prepareForValidation($input),
            $this->rules()
        )->validate();
    }
}
