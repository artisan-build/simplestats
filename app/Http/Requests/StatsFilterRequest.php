<?php

namespace App\Http\Requests;

use App\Enums\ComparisonType;
use App\Enums\StatsRangePreset;
use App\Enums\StatsRangeType;
use App\Models\CustomStatsRangePreset;
use App\Models\Project;
use App\Models\StatsCustomEventName;
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
use App\Rules\ValidTeamModel;
use App\Services\StatsService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
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

        // make the tracking filter work and set the projectId manually if only one project exists...
        if ($projects->count() === 1) {
            $project = $projects->first();
            $this->merge([
                'projectId' => $project->id,
            ]);
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
            ...$this->sharedFilterRules(),
            'projectId' => [
                'sometimes',
                'integer',
                new ValidTeamModel(Project::class),
            ],
            'groupType' => [
                'sometimes',
                'string',
                Rule::in(array_keys(StatsService::getStatsTypes()->toArray())),
            ],
            'preset' => [
                'sometimes',
                'string',
                function ($attribute, $value, $fail) {
                    if (StatsRangePreset::tryFrom($value) !== null) {
                        return;
                    }

                    if (Str::startsWith($value, 'custom_')) {
                        $exists = CustomStatsRangePreset::where('id', (int) Str::after($value, 'custom_'))
                            ->where('user_id', $this->user()->id)
                            ->exists();

                        if ($exists) {
                            return;
                        }
                    }

                    $fail('The selected preset is invalid.');
                },
            ],
        ];
    }

    /**
     * Shared validation rules used by both web and API filter requests.
     */
    public function sharedFilterRules(): array
    {
        return [
            'rangeType' => ['sometimes', Rule::enum(StatsRangeType::class)],
            'rangeAmount' => ['sometimes', 'integer'],
            'rangeStart' => ['sometimes', 'date'],
            'comparison' => [
                'sometimes',
                new Enum(ComparisonType::class),
            ],
            'trackReferer' => [
                'sometimes',
                'integer',
                new ValidTeamModel(StatsTrackReferer::class),
            ],
            'trackSource' => [
                'sometimes',
                'integer',
                new ValidTeamModel(StatsTrackSource::class),
            ],
            'trackMedium' => [
                'sometimes',
                'integer',
                new ValidTeamModel(StatsTrackMedium::class),
            ],
            'trackCampaign' => [
                'sometimes',
                'integer',
                new ValidTeamModel(StatsTrackCampaign::class),
            ],
            'trackTerm' => [
                'sometimes',
                'integer',
                new ValidTeamModel(StatsTrackTerm::class),
            ],
            'trackContent' => [
                'sometimes',
                'integer',
                new ValidTeamModel(StatsTrackContent::class),
            ],
            'locationCountry' => [
                'sometimes',
                'integer',
                new ValidTeamModel(StatsLocationCountry::class),
            ],
            'locationRegion' => [
                'sometimes',
                'integer',
                new ValidTeamModel(StatsLocationRegion::class),
            ],
            'locationCity' => [
                'sometimes',
                'integer',
                new ValidTeamModel(StatsLocationCity::class),
            ],
            'deviceType' => [
                'sometimes',
                'integer',
                new ValidTeamModel(StatsDeviceType::class),
            ],
            'devicePlatform' => [
                'sometimes',
                'integer',
                new ValidTeamModel(StatsDevicePlatform::class),
            ],
            'deviceBrowser' => [
                'sometimes',
                'integer',
                new ValidTeamModel(StatsDeviceBrowser::class),
            ],
            'pageEntry' => [
                'sometimes',
                'integer',
                new ValidTeamModel(StatsPageEntry::class),
            ],
            'customEventName' => [
                'sometimes',
                'integer',
                new ValidTeamModel(StatsCustomEventName::class),
            ],
            'statsType' => [
                'sometimes',
                'string',
                Rule::in(StatsService::getStatsTypesCamelFlat()->toArray()),
            ],
            'statsSort' => [
                'sometimes',
                'string',
                Rule::in(['name', 'net', 'reg', 'visitors', 'cr', 'dau', 'total', 'triggered']),
            ],
            'limit' => [
                'sometimes',
                'integer',
            ],
        ];
    }
}
