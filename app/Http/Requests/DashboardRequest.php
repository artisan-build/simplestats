<?php

namespace App\Http\Requests;

use App\Enums\StatsRangeType;
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

class DashboardRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation()
    {
        $projects = $this->user()->currentTeam->projects();

        //make the tracking filter work and set the projectId manually if only one project exists...
        if ($projects->count() === 1) {
            $project = $projects->first();
            $this->merge([
                'projectId' => $project->id,
            ]);

            $instances = $project->instances();
            if ($instances->count() === 1) {
                $this->merge([
                    'instanceId' => $instances->first()->id,
                ]);
            }
        }
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
                    $query->where([
                        'team_id' => $this->user()->current_team_id,
                    ]);
                }),
            ],
            'instanceId' => [
                'sometimes',
                'integer',
                Rule::exists(Instance::class, 'id')->where(function ($query) {
                    $query->where([
                        'project_id' => $this->projectId,
                        'team_id' => $this->user()->current_team_id,
                    ]);
                }),
            ],
            'rangeType' => ['sometimes', Rule::enum(StatsRangeType::class)],
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
            'extendedTracking' => [
                'sometimes',
                'integer'
            ]
        ];
    }
}
