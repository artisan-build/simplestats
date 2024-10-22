<?php

namespace App\Http\Requests;

use App\Services\StatsService;
use Illuminate\Foundation\Http\FormRequest;

class ProjectRequest extends FormRequest
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
            'name' => ['required', 'string', 'min:1', 'max:30'],
            'usage' => ['required', 'integer', 'min:1', 'max:'.StatsService::getTrackingUsageBitsCount()],
        ];
    }
}
