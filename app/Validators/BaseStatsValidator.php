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
