@component('mail::message')
    Hello {{ $user->name }},<br>
    <br>
    {{ $message }}
<br>

@endcomponent
