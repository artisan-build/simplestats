<x-mail::layout>
{{-- Header --}}
<x-slot:header>
<x-mail::header :url="config('app.url')">
<img src="{{ config('app.branding.app_logo_mail') }}" width="200" height="30" alt="{{ config('app.name') }}" title="{{ config('app.name') }}" />
{{--{{ config('app.name') }}--}}
</x-mail::header>
</x-slot:header>

{{-- Body --}}

<div style="word-wrap: break-word; overflow-wrap: break-word;">
{{ Illuminate\Mail\Markdown::parse($slot) }}
</div>

{{-- Subcopy --}}
@isset($subcopy)
<x-slot:subcopy>
<x-mail::subcopy>
{{ $subcopy }}
</x-mail::subcopy>
</x-slot:subcopy>
@endisset

{{-- Footer --}}
<x-slot:footer>
<x-mail::footer>
© {{ date('Y') }} {{ config('app.name') }}. @lang('All rights reserved.')
</x-mail::footer>
</x-slot:footer>
</x-mail::layout>
