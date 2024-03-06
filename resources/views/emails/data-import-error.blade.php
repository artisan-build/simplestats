@component('mail::message')
    Your {{ $dataImport->type->value }} data import failed with an error.<br>
    Please contact the Administrator or recheck your import file!<br>
    <br>
    Learn more here: <a href="/docs/data-import.html" target="_blank">Data Import</a>
@endcomponent
