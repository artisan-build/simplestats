<?php

namespace App\Http\Requests;

use App\Enums\StatsRange;
use App\Models\Instance;
use App\Models\Project;
use App\Models\StatsTrackCampaign;
use App\Models\StatsTrackContent;
use App\Models\StatsTrackMedium;
use App\Models\StatsTrackSource;
use App\Models\StatsTrackTerm;
use App\Rules\ValidProjectModelOrNegative;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StatsDetailsRequest extends FormRequest
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
            'projectId' => [
                'sometimes',
                'integer',
                Rule::exists(Project::class, 'id')->where(function ($query) {
                    $query->where('id', $this->projectId);
                }),
            ],
            'instanceId' => [
                'sometimes',
                'integer',
                Rule::exists(Instance::class, 'id')->where(function ($query) {
                    $query->where('project_id', $this->projectId);
                }),
            ],
            'rangeType' => ['sometimes', Rule::enum(StatsRange::class)],
            'rangeAmount' => ['sometimes', 'integer'],
            'rangeStart' => ['sometimes', 'date'],
            'trackSource' => [
                'sometimes',
                'integer',
                new ValidProjectModelOrNegative($this->projectId, StatsTrackSource::class),
            ],
            'trackMedium' => [
                'sometimes',
                'integer',
                new ValidProjectModelOrNegative($this->projectId, StatsTrackMedium::class),
            ],
            'trackCampaign' => [
                'sometimes',
                'integer',
                new ValidProjectModelOrNegative($this->projectId, StatsTrackCampaign::class),
            ],
            'trackTerm' => [
                'sometimes',
                'integer',
                new ValidProjectModelOrNegative($this->projectId, StatsTrackTerm::class),
            ],
            'trackContent' => [
                'sometimes',
                'integer',
                new ValidProjectModelOrNegative($this->projectId, StatsTrackContent::class),
            ],
        ];
    }
}
