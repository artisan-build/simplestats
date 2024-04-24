@component('mail::message')
    Your <i>{{ $dataImport->type->value }}</i> data import failed with the following message:<br>
    <span style="color: #dc2626; font-style: italic; font-weight: 600;">{{ $dataImport->trackedJob->output }}</span><br>
    <br>
    Please fix the issues and start a new import!<br>
    @if ($dataImport->rows_imported > 0)
    So far we already imported <strong>{{ $dataImport->rows_imported }}</strong> rows
    from id <strong>{{ $dataImport->id_from }}</strong> to id <strong>{{ $dataImport->id_to }}</strong>.<br>
    <br>
    If you want to speed things up, you may remove all rows that we've already imported from your data import file.
    But it is also safe to just run it again...<br>
    @endif
    <br>
    Import started at: {{ $dataImport->trackedJob->created_at }}<br>
    Import failed at: {{ $dataImport->trackedJob->finished_at }}<br>
    <br>
    Learn more here: <a href="{{ config('app.url') }}/docs/how-to-import-existing-data.html" target="_blank">Data Import</a>
@endcomponent
