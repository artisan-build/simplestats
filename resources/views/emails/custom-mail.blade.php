@component('mail::message')
    Hello {{ $user->name }},<br>
    <br>
    {!! $message !!}<br>
    <br>
    Happy analysing,<br>
    The SimpleStats-Team<br>
<br>

@endcomponent
