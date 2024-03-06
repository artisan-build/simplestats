<?php

namespace App\Http\Requests\Api;

use App\Validators\StatsLoginValidator;
use Illuminate\Foundation\Http\FormRequest;

class StatsLoginRequest extends FormRequest
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
        $this->merge((new StatsLoginValidator())->prepareForValidation($this->all()));
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return (new StatsLoginValidator())->rules();
    }
}
