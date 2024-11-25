<?php

namespace App\Http\Requests;

use App\Enums\ComparisonType;
use App\Enums\StatsRangeType;
use App\Models\Instance;
use App\Models\Project;
use App\Models\StatsDeviceBrowser;
use App\Models\StatsDevicePlatform;
use App\Models\StatsDeviceType;
use App\Models\StatsLocationCity;
use App\Models\StatsLocationRegion;
use App\Models\StatsPageEntry;
use App\Models\StatsTrackCampaign;
use App\Models\StatsTrackContent;
use App\Models\StatsLocationCountry;
use App\Models\StatsTrackMedium;
use App\Models\StatsTrackReferer;
use App\Models\StatsTrackSource;
use App\Models\StatsTrackTerm;
use App\Models\StatsUser;
use App\Rules\ValidProjectModel;
use App\Services\StatsService;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class StatsFilterRequest extends FormRequest
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
                new ValidProjectModel($this->projectId, Project::class),
            ],
            'instanceId' => [
                'sometimes',
                'integer',
                new ValidProjectModel($this->projectId, Instance::class),
            ],
            'rangeType' => ['sometimes', Rule::enum(StatsRangeType::class)],
            'rangeAmount' => ['sometimes', 'integer'],
            'rangeStart' => ['sometimes', 'date'],
            'trackReferer' => [
                'sometimes',
                'integer',
                new ValidProjectModel($this->projectId, StatsTrackReferer::class),
            ],
            'trackSource' => [
                'sometimes',
                'integer',
                new ValidProjectModel($this->projectId, StatsTrackSource::class),
            ],
            'trackMedium' => [
                'sometimes',
                'integer',
                new ValidProjectModel($this->projectId, StatsTrackMedium::class),
            ],
            'trackCampaign' => [
                'sometimes',
                'integer',
                new ValidProjectModel($this->projectId, StatsTrackCampaign::class),
            ],
            'trackTerm' => [
                'sometimes',
                'integer',
                new ValidProjectModel($this->projectId, StatsTrackTerm::class),
            ],
            'trackContent' => [
                'sometimes',
                'integer',
                new ValidProjectModel($this->projectId, StatsTrackContent::class),
            ],
            'locationCountry' => [
                'sometimes',
                'integer',
                new ValidProjectModel($this->projectId, StatsLocationCountry::class),
            ],
            'locationRegion' => [
                'sometimes',
                'integer',
                new ValidProjectModel($this->projectId, StatsLocationRegion::class),
            ],
            'locationCity' => [
                'sometimes',
                'integer',
                new ValidProjectModel($this->projectId, StatsLocationCity::class),
            ],
            'deviceType' => [
                'sometimes',
                'integer',
                new ValidProjectModel($this->projectId, StatsDeviceType::class),
            ],
            'devicePlatform' => [
                'sometimes',
                'integer',
                new ValidProjectModel($this->projectId, StatsDevicePlatform::class),
            ],
            'deviceBrowser' => [
                'sometimes',
                'integer',
                new ValidProjectModel($this->projectId, StatsDeviceBrowser::class),
            ],
            'pageEntry' => [
                'sometimes',
                'integer',
                new ValidProjectModel($this->projectId, StatsPageEntry::class),
            ],
            'statsType' => [
                'sometimes',
                'string',
                Rule::in(StatsService::getStatsTypesCamelFlat()->toArray())
            ],
            'statsSort' => [
                'sometimes',
                'string',
                Rule::in(['name', 'net', 'reg', 'visitors', 'cr', 'dau'])
            ],
            'groupType' => [
                'sometimes',
                'string',
                Rule::in(array_keys(StatsService::getStatsTypes()->toArray()))
            ],
            'limit' => [
                'sometimes',
                'integer',
            ],
            'comparison' => [
                'sometimes',
                new Enum(ComparisonType::class),
            ],
        ];
    }
}
