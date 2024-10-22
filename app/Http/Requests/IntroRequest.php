<?php

namespace App\Http\Requests;

use App\Services\StatsService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Spatie\ValidationRules\Rules\Currency;

class IntroRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->currentTeam->instances()->count() <= 0;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'team' => ['sometimes', 'required', 'string', 'min:1', 'max:30'],
            'currency' => ['sometimes', 'required', new Currency()],
            'timezone' => ['sometimes', 'required', Rule::in(timezone_identifiers_list())],
            'project' => ['sometimes', 'required', 'string', 'min:1', 'max:30'],
            'instance' => ['sometimes', 'required', 'string', 'min:1', 'max:30'],
            'usage' => ['sometimes', 'required', 'integer', 'min:1', 'max:'.StatsService::getTrackingUsageBitsCount()],
        ];
    }
}
