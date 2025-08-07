<?php

namespace App\Http\Requests;

use App\Enums\DataImportType;
use App\Models\Project;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

class DataImportRequest extends FormRequest
{
    const MIMETYPES = ['xls', 'xlsx', 'csv', 'txt'];

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
            'project_id' => [
                'required',
                'integer',
                Rule::exists(Project::class, 'id')->where(function ($query) {
                    $query->where([
                        'team_id' => $this->user()->current_team_id,
                    ]);
                }),
            ],
            'type' => [
                'required',
                Rule::enum(DataImportType::class),
            ],
            'file' => [
                'required',
                File::types(self::MIMETYPES),
            ],
        ];
    }
}
