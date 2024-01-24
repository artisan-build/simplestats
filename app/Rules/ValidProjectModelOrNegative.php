<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

readonly class ValidProjectModelOrNegative implements ValidationRule
{
    public function __construct(
        private ?int $projectId,
        private string $model,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value == -1 || $this->model::where('id', $value)->where('project_id', $this->projectId)->exists()) {
            return;
        }

        $fail('The :attribute is not valid!');
    }
}
