@component('mail::message')
![Chart]({{ asset('images/undraw/undraw_winners_fre4.png') }})<br>
<br>
# New registration on {{ $projectName }}

## Someone just registered on {{ $projectName }}.

@component('mail::table')
| Details|&nbsp;|
|:-------|:-----|
|**User ID**|{{ $statsUser->id }}|
|**Time**|{{ $statsUser->time->format('H:i:s') }}|
@foreach($statsTypes as $statsType)
@if($stat = $statsUser->stats['stats'.ucfirst(Str::camel($statsType['type']))] ?? null)
|**{{ str($statsType['title'])->singular() }}**|{{ $stat['name'] }}|
@endif
@endforeach
@endcomponent

<p style="text-align: center">Want to see more details?</p>
@component('mail::button', ['url' => route('dashboard')])
View Dashboard
@endcomponent

---

<small>Don't want to receive these alerts? <a href="{{ route('notifications.index') }}" target="_blank">Change notification settings</a></small>
@endcomponent
