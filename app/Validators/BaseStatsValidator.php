<?php

namespace App\Validators;

use App\Enums\DataImportType;
use App\Imports\CountImportRows;
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

    public function validate($input): void
    {
        Validator::make(
            $this->prepareForValidation($input),
            $this->rules()
        )->validate();
    }

    public function validateImportFields(UploadedFile $file, array $allowedFields): void
    {
        $sheet = Excel::toArray(new HeadingRowImport(), $file);
        $headings = $sheet[0][0];

        if (!empty(array_diff($headings, $allowedFields))) {
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
     * So we can't really measure how  much more data with the current plan is allowed.
     * For now, we leave it like this and use it as a indicator for new data, but as I wrote,
     * it is not 100% correct, especially if it would be an upsert...
     *
     * @param  UploadedFile  $file
     * @param  string  $statsModel
     * @param  DataImportType  $importType
     * @return void
     */
    public function validateImportCount(UploadedFile $file, string $statsModel, DataImportType $importType): void
    {
        // read file without importing
        $countRows = new CountImportRows();
        Excel::import($countRows, $file);
        $rowsCount = $countRows->rowsCount;

        if (! Gate::check('create', [$statsModel, $rowsCount])) {
            $allowedCount = auth()->user()->team->owner->sparkPlan()->options[$importType->value];
            $countLeft = ($statsModel::withoutGlobalScope('instance')->count() + $rowsCount) - $allowedCount;

            throw ValidationException::withMessages([
                'file' => ["Too many data to import! You have only $countLeft {$importType->value} left to track. Consider upgrading your plan!"],
            ]);
        }
    }
}
