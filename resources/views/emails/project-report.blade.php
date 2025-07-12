@component('mail::message')
# {{ $frequency->title() }} report

## {{ $timeRange }}

Here is your {{ $frequency->value }} report for {{ $projectName }}:

@component('mail::table')
|Laravel|Table|Example|
|:-|:-|:-|
|Col 2 is|Centered|$10|
|Col 3 is|Right-Aligned |$20|
@endcomponent

Unsubscibe
@endcomponent
