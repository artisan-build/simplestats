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
    ) {
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $projectModel = $this->model::where('id', $value);

        // If it's the project model itself, the id check above is enough
        if ($this->model != Project::class && $this->projectId) {
            $projectModel->where('project_id', $this->projectId);
        }

        if ($value == -1 || $value == 0 || $projectModel->exists()) {
            return;
        }

        $fail('The :attribute is not valid!');
    }
}
