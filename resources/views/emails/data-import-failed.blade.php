@component('mail::message')
    Your {{ $dataImport->type->value }} data import failed with the following message:<br>
    {{ $dataImport->trackedJob->output }}<br>
    <br>
    Import started at: {{ $dataImport->trackedJob->created_at }}<br>
    Import failed at: {{ $dataImport->trackedJob->finished_at }}<br>
    <br>
    So far we imported {{ $dataImport->rows_count }} rows from id {{ $dataImport->id_from }} to id {{ $dataImport->id_to }}.<br>
    <br>
    Learn more here: <a href="/docs/data-import.html" target="_blank">Data Import</a>
@endcomponent
