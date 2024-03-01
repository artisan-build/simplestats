<?php

namespace App\Validators;

use Illuminate\Support\Facades\Validator;

class BaseValidator
{
    public function prepareForValidation(array $input): array
    {
        return $input;
    }

    public function rules(): array
    {
        return [];
    }

    public function validate($input): void
    {
        Validator::make(
            $this->prepareForValidation($input),
            $this->rules()
        )->validate();
    }
}
