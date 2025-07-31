@php use App\Models\StatsUser; @endphp
@component('mail::message')
![Chart]({{ asset('images/undraw/undraw_happy-announcement_23nf.png') }})<br>
<br>
# New payment received on {{ $projectName }}

## You’ve just received a new payment on *{{ $projectName }}*.

@component('mail::table')
| Details|&nbsp;|
|:-------|:-----|
|**Amount**|{{ Number::currency($statsPayment->gross/100, in: $statsPayment->currency, precision: 2) }}|
@if($statsPerson instanceof StatsUser)
|**User ID**|{{ $statsPerson->id }}|
@endif
|**Time**|{{ $statsPayment->time->format('H:i:s') }}|
@foreach($statsTypes as $statsType)
@if($stat = $statsPerson->stats['stats'.ucfirst($statsType['type'])] ?? null)
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
