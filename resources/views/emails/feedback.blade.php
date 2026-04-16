@component('mail::message')
**{{ $type->title() }}** from {{ $user->name }} ({{ $user->email }}):<br>
<br>
{{ $feedbackMessage }}<br>
<br>
You can reply directly to this email to respond to the user.
@endcomponent
