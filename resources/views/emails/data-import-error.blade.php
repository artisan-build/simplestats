@component('mail::message')
    Your {{ $dataImport->type->value }} data import failed with an unexpected error.<br>
    <br>
    <strong>Error code:</strong><br>
    <span style="color: #dc2626; font-style: italic;">{{ $errorCode }}</span><br>
    <br>
    Please contact the support or recheck the data structure of your import file!<br>
    <br>
    Learn more here: <a href="{{ config('app.url') }}/docs/data-import.html" target="_blank">Data Import</a>
@endcomponent
