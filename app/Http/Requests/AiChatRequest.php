<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AiChatRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) ($this->user()?->currentTeam?->owner?->sparkPlan()->options['AiAssistant'] ?? false);
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
