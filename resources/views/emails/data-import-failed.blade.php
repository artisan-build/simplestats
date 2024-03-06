@component('mail::message')
    Your {{ $dataImport->type->value }} data import failed with the following message:<br>
    {{ $dataImport->trackedJob->output }}<br>
    <br>
    Learn more here: <a href="/docs/data-import.html" target="_blank">Data Import</a>
@endcomponent
