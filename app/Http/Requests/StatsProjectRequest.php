<?php

namespace App\Http\Requests;

use App\Enums\StatsRange;
use App\Models\Instance;
use App\Models\StatsTrackCampaign;
use App\Models\StatsTrackContent;
use App\Models\StatsTrackMedium;
use App\Models\StatsTrackSource;
use App\Models\StatsTrackTerm;
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
            'instanceId' => [
                'sometimes',
                'integer',
                Rule::exists(Instance::class, 'id')->where(function ($query) {
                    $query->where('project_id', $this->route('project')->id);
                }),
            ],
            'rangeType' => ['sometimes', Rule::enum(StatsRange::class)],
            'rangeAmount' => ['sometimes', 'integer'],
            'rangeStart' => ['sometimes', 'date'],
            'trackSource' => [
                'sometimes',
                'integer',
                Rule::exists(StatsTrackSource::class, 'id')->where(function ($query) {
                    $query->where('project_id', $this->route('project')->id);
                }),
            ],
            'trackMedium' => [
                'sometimes',
                'integer',
                Rule::exists(StatsTrackMedium::class, 'id')->where(function ($query) {
                    $query->where('project_id', $this->route('project')->id);
                }),
            ],
            'trackCampaign' => [
                'sometimes',
                'integer',
                Rule::exists(StatsTrackCampaign::class, 'id')->where(function ($query) {
                    $query->where('project_id', $this->route('project')->id);
                }),
            ],
            'trackTerm' => [
                'sometimes',
                'integer',
                Rule::exists(StatsTrackTerm::class, 'id')->where(function ($query) {
                    $query->where('project_id', $this->route('project')->id);
                }),
            ],
            'trackContent' => [
                'sometimes',
                'integer',
                Rule::exists(StatsTrackContent::class, 'id')->where(function ($query) {
                    $query->where('project_id', $this->route('project')->id);
                }),
            ],
        ];
    }
}
