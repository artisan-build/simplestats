@component('mail::message')
    Your <i>{{ $dataImport->type->value }}</i> data import failed with the following message:<br>
    <span style="color: #dc2626; font-style: italic; font-weight: 600;">{{ $dataImport->trackedJob->output }}</span><br>
    <br>
    Please fix the issues and start a new import!
    If you want to speed things up, you may remove all rows we already imported from your data import file.
    But it is also safe to just run it again.<br>
    <br>
    So far we already imported <strong>{{ $dataImport->rows_count }}</strong> rows
    from id <strong>{{ $dataImport->id_from }}</strong> to id <strong>{{ $dataImport->id_to }}</strong>.<br>
    <br>
    Import started at: {{ $dataImport->trackedJob->created_at }}<br>
    Import failed at: {{ $dataImport->trackedJob->finished_at }}<br>
    <br>
    Learn more here: <a href="{{ config('app.url') }}/docs/data-import.html" target="_blank">Data Import</a>
@endcomponent
