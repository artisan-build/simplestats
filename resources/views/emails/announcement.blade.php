@component('mail::message')

Hey {{ filled($recipientName) ? $recipientName : 'there' }},

{!! str($markdownContent)->markdown()->sanitizeHtml() !!}

Happy tracking,<br>
The SimpleStats-Team

@endcomponent
