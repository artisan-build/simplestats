<?php

namespace App\Http\Requests;

use App\Helpers\AppHelper;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CampaignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'project_id' => ['required', 'integer', Rule::exists('projects', 'id')->where('team_id', AppHelper::getTeamId())],
            'name' => ['required', 'string', 'max:255'],
        ];
    }
}
