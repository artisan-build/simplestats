@component('mail::message')
    Your {{ $dataImport->type->value }} data import has been finished successfully!<br>
    <br>
    Import started at: {{ $dataImport->trackedJob->created_at }}<br>
    Import finished at: {{ $dataImport->trackedJob->finished_at }}<br>
    <br>
    We imported {{ $dataImport->rows_count }} rows from id {{ $dataImport->id_from }} to id {{ $dataImport->id_to }}.
@endcomponent
