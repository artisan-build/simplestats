<?php

namespace App\Validators;

use App\Enums\DataImportType;
use App\Models\DataImport;
use Gate;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\HeadingRowImport;

class BaseStatsValidator
{
    public function prepareForValidation(array $input): array
    {
        return $input;
    }

    public function rules(): array
    {
        // we do not allow logging in the future
        return [
            'time' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
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

    /**
     * This approach is unfortunately not compatible with upsert!
     * We count what's the plan limit, then we count the current data in the db
     * and then we count how much data/rows would be imported new.
     * But it is not clear here which of the imported data will be an upsert (cause the id is already in our system).
     * So we can't really measure how much more data with the current plan is allowed.
     * For now, we leave it like this and use it as an indicator for new data, but as I wrote,
     * it is not 100% correct, especially if it is an upsert...
     */
    public function validateImportCount(DataImport $dataImport): void
    {
        $importRowsCount = $dataImport->rows_max;
        $statsModel = $dataImport->type->getImporter($dataImport)->getModelClass();

        if (! Gate::check('create', [$statsModel, $importRowsCount])) {
            $allowedCount = auth()->user()->team->owner->sparkPlan()->options[$dataImport->type->getModel()];
            $countLeft = abs($allowedCount - $statsModel::withoutGlobalScope('instance')->count());

            throw ValidationException::withMessages([
                'file' => ["You're exceeding the import limit! Your attempt to import $importRowsCount rows exceeds your remaining quota of $countLeft rows. Please consider upgrading your plan to import more rows."],
            ]);
        }
    }
}
