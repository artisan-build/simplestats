@component('mail::message')

Hey {{ $recipientName }},

{!! str($markdownContent)->markdown()->sanitizeHtml() !!}

Happy tracking,<br>
The SimpleStats-Team

@endcomponent
