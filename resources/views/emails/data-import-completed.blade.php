@component('mail::message')
    Your {{ $dataImport->type->value }} data import has been completed successfully!<br>
    <br>
    We've imported <strong>{{ $dataImport->rows_imported }}</strong> rows
    from id <strong>{{ $dataImport->id_from }}</strong> to id <strong>{{ $dataImport->id_to }}</strong>.<br>
    <br>
    Import started at: {{ $dataImport->trackedJob->created_at }}<br>
    Import finished at: {{ $dataImport->trackedJob->finished_at }}
@endcomponent
