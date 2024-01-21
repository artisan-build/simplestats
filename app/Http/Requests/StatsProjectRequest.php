<?php

namespace App\Http\Requests;

use App\Models\Instance;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StatsProjectRequest extends FormRequest
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
            'instance_id' => [
                'sometimes',
                'integer',
                Rule::exists(Instance::class, 'id')->where(function ($query) {
                    $query->where('project_id', $this->route('project')->id);
                }),
            ],
        ];
    }
}
