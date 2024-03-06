<x-mail::layout>
{{-- Header --}}
<x-slot:header>
<x-mail::header :url="config('app.url')">
<img src="{{ asset('/svg/logo.svg') }}" alt="Logo" title="Logo" />
{{--{{ config('app.name') }}--}}
</x-mail::header>
</x-slot:header>

{{-- Body --}}

<div style="word-wrap: break-word; overflow-wrap: break-word;">
{{ $slot }}
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
