<?php

namespace App\Validators;

use App\Enums\StatsRangeType;
use App\Models\DataImport;
use Gate;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class BaseStatsValidator
{
    public function prepareForValidation(array $input): array
    {
        $input['time'] = Carbon::parse($input['time'])->tz('UTC')->format(StatsRangeType::DATE_FORMAT.' P');

        if (isset($input['stats_user_time'])) {
            $input['stats_user_time'] = Carbon::parse($input['stats_user_time'])->tz('UTC')->format(StatsRangeType::DATE_FORMAT.' P');
        }

        return $input;
    }

    public function rules(): array
    {
        return [
            'time' => [
                'required',
                'date_format:Y-m-d H:i:s P',
            ],
            'stats_user_time' => [
                'sometimes',
                'date_format:Y-m-d H:i:s P',
            ],
            'client_version' => [
                'nullable',
                'string',
            ],
        ];
    }

    public function validate($input)
    {
        return Validator::make(
            $this->prepareForValidation($input),
            $this->rules()
        )->validate();
    }

    public function validateImportFields(DataImport $dataImport, array $importFields): void
    {
        $allowedFields = array_keys($dataImport->type->getImporter($dataImport)->rules());

        if (! empty(array_diff($importFields, $allowedFields))) {
            throw ValidationException::withMessages([
                'file' => ['Only the following fields are allowed: '.implode(', ', $allowedFields)],
            ]);
        }
    }
}
