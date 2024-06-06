@component('mail::message')
Hi {{ $teamOwner->name }},<br>
<br>
@foreach($alarms as $alarm)
{{ $alarm['text'] }}<br>
@endforeach
<br>
@if($alarms[0]['percent'] < 100)
Once you reach the limit, we will stop tracking and storing your data!<br>
<br>
@endif
Consider upgrading your subscription plan.<br>
@component('mail::button', ['url' => route('spark.portal')])
{{ __('Upgrade Plan') }}
@endcomponent
@endcomponent
