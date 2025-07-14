@component('mail::message')
# {{ $frequency->title() }} report

## {{ $timeRange }}<br>
<br>
Here is your {{ $frequency->value }} report for <strong>{{ $projectName }}</strong>:<br>
<br>
<br>
@foreach($statsGroups as $statsGroup)
Your top <strong>{{ $statsGroup['descr'] }}</strong> are:
@component('mail::table')
|Laravel|Table|Example|
|:-|:-|:-|
|Col 2 is|Centered|$10|
|Col 3 is|Right-Aligned |$20|
@endcomponent
<br>
@endforeach

<a href="{{ route('notifications.index') }}" target="_blank">Change notification settings</a>
@endcomponent
