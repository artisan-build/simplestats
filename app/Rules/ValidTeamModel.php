<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

readonly class ValidTeamModel implements ValidationRule
{
    public function __construct(
        private string $model,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $teamModel = $this->model::where('id', $value);

        if ($value == -1 || $value == 0 || $teamModel->exists()) {
            return;
        }

        $fail('The :attribute is not valid!');
    }
}
