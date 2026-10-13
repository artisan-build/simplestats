<?php

namespace App\Http\Requests\Api;

use App\Validators\StatsCustomEventValidator;
use Illuminate\Foundation\Http\FormRequest;

class StatsCustomEventRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge((new StatsCustomEventValidator)->prepareForValidation($this->all()));
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return (new StatsCustomEventValidator)->rules();
    }
}
