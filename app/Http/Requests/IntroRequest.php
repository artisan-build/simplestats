<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Spatie\ValidationRules\Rules\Currency;

class IntroRequest extends FormRequest
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
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'team' => ['sometimes', 'required', 'string'],
            'currency' => ['sometimes', 'required', new Currency()],
            'project' => ['sometimes', 'required', 'string'],
            'instance' => ['sometimes', 'required', 'string'],
        ];
    }
}
