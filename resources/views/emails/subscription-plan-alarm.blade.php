@component('mail::message')
Hi {{ $teamOwner->name }},<br>
<br>
@foreach($alarms as $alarm)
{{ $alarm['text'] }}<br>
@endforeach
<br>
@if($alarms[0]['percent'] < 100)
Once you hit the limit, we'll stop tracking and storing your data!<br>
<br>
@endif
Consider upgrading your subscription to keep tracking uninterrupted.<br>
@component('mail::button', ['url' => route('spark.portal')])
{{ __('Upgrade Plan') }}
@endcomponent
@endcomponent
