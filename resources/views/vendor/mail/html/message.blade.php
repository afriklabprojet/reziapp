<x-mail::layout>
{{-- Header --}}
<x-slot:header>
<x-mail::header :url="config('app.url')">
{{ config('app.name') }}
</x-mail::header>
</x-slot:header>

{{-- Body --}}
{!! $slot !!}

{{-- Subcopy --}}
@isset($subcopy)
<x-slot:subcopy>
<x-mail::subcopy>
{!! $subcopy !!}
</x-mail::subcopy>
</x-slot:subcopy>
@endisset

{{-- Footer --}}
<x-slot:footer>
<x-mail::footer>
© {{ date('Y') }} REZI — La plateforme de résidences meublées en Côte d'Ivoire.<br>
<a href="{{ config('app.url') }}">rezi.ci</a> · <a href="mailto:contact@rezi.ci">contact@rezi.ci</a>
</x-mail::footer>
</x-slot:footer>
</x-mail::layout>
