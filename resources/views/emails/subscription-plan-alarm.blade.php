@component('mail::message')
Hi {{ $teamOwner->name }},<br>
<br>
@foreach($alarms as $alarm)
{{ $alarm }}<br>
@endforeach
<br>
Once you reach the limit, we will stop tracking and storing your data!<br>
<br>
Consider upgrading your subscription plan.<br>
@component('mail::button', ['url' => route('spark.portal')])
{{ __('Upgrade Plan') }}
@endcomponent
@endcomponent
