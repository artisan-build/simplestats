<?php

namespace App\Http\Requests;

use App\Services\Ai\TeamAiConfig;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TeamAiSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('team'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'ai_provider' => ['nullable', 'string', Rule::in(TeamAiConfig::allowedProviders())],
            'ai_model' => ['nullable', 'string', 'max:100'],
            'ai_api_key' => [
                // A stored key survives provider/model edits without re-entry.
                Rule::requiredIf(fn () => $this->filled('ai_provider') && ! $this->route('team')->ai_api_key),
                'nullable',
                'string',
                'max:200',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'ai_api_key.required' => 'An API key is required when using your own AI provider.',
        ];
    }
}
