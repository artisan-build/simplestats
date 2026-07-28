<?php

namespace App\Http\Requests;

use App\Helpers\AppHelper;
use App\Models\Project;
use Illuminate\Foundation\Http\FormRequest;

class AiChatRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) ($this->user()?->currentTeam?->owner?->sparkPlan()->options['AiAssistant'] ?? false);
    }

    protected function prepareForValidation(): void
    {
        // The frontend sends the project's public uuid.
        $this->merge([
            'project_id' => AppHelper::resolvePublicId(Project::class, $this->input('project_id') ?? 0),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'message' => ['required', 'string', 'max:2000'],
            'conversation_id' => ['nullable', 'uuid'],
            'project_id' => ['nullable', 'integer', 'min:0'],
            // Dashboard range preset as soft context; unknown values (e.g.
            // custom presets) are ignored via tryFrom in the controller.
            'context_preset' => ['nullable', 'string', 'max:50'],
        ];
    }
}
