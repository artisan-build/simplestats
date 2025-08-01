<?php

namespace App\Http\Requests;

use App\Enums\ComparisonType;
use App\Enums\StatsRangePreset;
use App\Enums\StatsRangeType;
use App\Models\Instance;
use App\Models\Project;
use App\Models\StatsDeviceBrowser;
use App\Models\StatsDevicePlatform;
use App\Models\StatsDeviceType;
use App\Models\StatsLocationCity;
use App\Models\StatsLocationCountry;
use App\Models\StatsLocationRegion;
use App\Models\StatsPageEntry;
use App\Models\StatsTrackCampaign;
use App\Models\StatsTrackContent;
use App\Models\StatsTrackMedium;
use App\Models\StatsTrackReferer;
use App\Models\StatsTrackSource;
use App\Models\StatsTrackTerm;
use App\Rules\ValidTeamOrProjectModel;
use App\Services\StatsService;
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

    protected function prepareForValidation(): void
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
                new ValidTeamOrProjectModel(Project::class),
            ],
            'instanceId' => [
                'sometimes',
                'integer',
                new ValidTeamOrProjectModel(Instance::class),
            ],
            'rangeType' => ['sometimes', Rule::enum(StatsRangeType::class)],
            'rangeAmount' => ['sometimes', 'integer'],
            'rangeStart' => ['sometimes', 'date'],
            'trackReferer' => [
                'sometimes',
                'integer',
                new ValidTeamOrProjectModel(StatsTrackReferer::class),
            ],
            'trackSource' => [
                'sometimes',
                'integer',
                new ValidTeamOrProjectModel(StatsTrackSource::class),
            ],
            'trackMedium' => [
                'sometimes',
                'integer',
                new ValidTeamOrProjectModel(StatsTrackMedium::class),
            ],
            'trackCampaign' => [
                'sometimes',
                'integer',
                new ValidTeamOrProjectModel(StatsTrackCampaign::class),
            ],
            'trackTerm' => [
                'sometimes',
                'integer',
                new ValidTeamOrProjectModel(StatsTrackTerm::class),
            ],
            'trackContent' => [
                'sometimes',
                'integer',
                new ValidTeamOrProjectModel(StatsTrackContent::class),
            ],
            'locationCountry' => [
                'sometimes',
                'integer',
                new ValidTeamOrProjectModel(StatsLocationCountry::class),
            ],
            'locationRegion' => [
                'sometimes',
                'integer',
                new ValidTeamOrProjectModel(StatsLocationRegion::class),
            ],
            'locationCity' => [
                'sometimes',
                'integer',
                new ValidTeamOrProjectModel(StatsLocationCity::class),
            ],
            'deviceType' => [
                'sometimes',
                'integer',
                new ValidTeamOrProjectModel(StatsDeviceType::class),
            ],
            'devicePlatform' => [
                'sometimes',
                'integer',
                new ValidTeamOrProjectModel(StatsDevicePlatform::class),
            ],
            'deviceBrowser' => [
                'sometimes',
                'integer',
                new ValidTeamOrProjectModel(StatsDeviceBrowser::class),
            ],
            'pageEntry' => [
                'sometimes',
                'integer',
                new ValidTeamOrProjectModel(StatsPageEntry::class),
            ],
            'statsType' => [
                'sometimes',
                'string',
                Rule::in(StatsService::getStatsTypesCamelFlat()->toArray()),
            ],
            'statsSort' => [
                'sometimes',
                'string',
                Rule::in(['name', 'net', 'reg', 'visitors', 'cr', 'dau']),
            ],
            'groupType' => [
                'sometimes',
                'string',
                Rule::in(array_keys(StatsService::getStatsTypes()->toArray())),
            ],
            'limit' => [
                'sometimes',
                'integer',
            ],
            'comparison' => [
                'sometimes',
                new Enum(ComparisonType::class),
            ],
            'preset' => [
                'sometimes',
                'string',
                Rule::enum(StatsRangePreset::class),
            ],
        ];
    }
}
