<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StatsUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'track_source' => ['nullable', 'string'],
            'track_medium' => ['nullable', 'string'],
            'track_campaign' => ['nullable', 'string'],
            'track_term' => ['nullable', 'string'],
            'track_content' => ['nullable', 'string'],
            'created_at' => ['required', 'date'],
        ];
    }
}
