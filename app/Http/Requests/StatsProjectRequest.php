<?php

namespace App\Http\Requests;

use App\Enums\StatsRange;
use App\Models\Instance;
use App\Models\StatsTrackCampaign;
use App\Models\StatsTrackContent;
use App\Models\StatsTrackMedium;
use App\Models\StatsTrackSource;
use App\Models\StatsTrackTerm;
use App\Rules\ValidProjectModelOrNegative;
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
                new ValidProjectModelOrNegative($this->route('project')->id, StatsTrackSource::class),
            ],
            'trackMedium' => [
                'sometimes',
                'integer',
                new ValidProjectModelOrNegative($this->route('project')->id, StatsTrackMedium::class),
            ],
            'trackCampaign' => [
                'sometimes',
                'integer',
                new ValidProjectModelOrNegative($this->route('project')->id, StatsTrackCampaign::class),
            ],
            'trackTerm' => [
                'sometimes',
                'integer',
                new ValidProjectModelOrNegative($this->route('project')->id, StatsTrackTerm::class),
            ],
            'trackContent' => [
                'sometimes',
                'integer',
                new ValidProjectModelOrNegative($this->route('project')->id, StatsTrackContent::class),
            ],
        ];
    }
}
