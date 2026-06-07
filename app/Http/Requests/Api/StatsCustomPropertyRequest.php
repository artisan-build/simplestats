<?php

namespace App\Http\Requests\Api;

use App\Validators\StatsCustomPropertyValidator;
use Illuminate\Foundation\Http\FormRequest;

class StatsCustomPropertyRequest extends FormRequest
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
        $this->merge((new StatsCustomPropertyValidator)->prepareForValidation($this->all()));
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return (new StatsCustomPropertyValidator)->rules();
    }
}
