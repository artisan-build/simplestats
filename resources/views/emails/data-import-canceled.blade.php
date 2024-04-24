@component('mail::message')
    Your <i>{{ $dataImport->type->value }}</i> data import has been canceled!<br>
    @if ($dataImport->rows_imported > 0)
    <br>
    So far we already imported <strong>{{ $dataImport->rows_imported }}</strong> rows
    from id <strong>{{ $dataImport->id_from }}</strong> to id <strong>{{ $dataImport->id_to }}</strong>.<br>
    <br>
    If you canceled the import cause you think you provided some wrong data, you can just re-import a fixed version,
    and it will overwrite the wrong records.<br>
    @endif
    <br>
    Import started at: {{ $dataImport->trackedJob->created_at }}<br>
    Import canceled at: {{ $dataImport->trackedJob->finished_at }}<br>
    <br>
    Learn more here: <a href="{{ config('app.url') }}/docs/how-to-import-existing-data.html" target="_blank">Data Import</a>
@endcomponent
