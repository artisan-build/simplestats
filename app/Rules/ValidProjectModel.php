<?php

namespace App\Rules;

use App\Models\Project;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

readonly class ValidProjectModel implements ValidationRule
{
    public function __construct(
        private ?int $projectId,
        private string $model,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // if its the project model itself, we just need to check the id...
        $validProjectModel = $this->model::where('id', $value)
            ->when($this->model != Project::class, function ($query) {
                $query->where('project_id', $this->projectId)->exists();
            });

        if ($value == -1 || $value == 0 || $validProjectModel) {
            return;
        }

        $fail('The :attribute is not valid!');
    }
}
