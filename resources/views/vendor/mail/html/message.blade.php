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
<div style="text-align: center; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
<p style="margin: 0 0 8px 0; font-size: 13px; color: #6b7280; line-height: 1.6;">
<strong style="color: #111827;">Rezi App</strong> &mdash; La plateforme de résidences meublées en Côte d'Ivoire.
</p>
<p style="margin: 0 0 12px 0; font-size: 13px; color: #6b7280;">
<a href="{{ config('app.url') }}" style="color: #f97316; text-decoration: none; font-weight: 600;">reziapp.ci</a>
&nbsp;&middot;&nbsp;
<a href="mailto:contact@reziapp.ci" style="color: #f97316; text-decoration: none; font-weight: 600;">contact@reziapp.ci</a>
&nbsp;&middot;&nbsp;
<a href="https://wa.me/2250700000000" style="color: #f97316; text-decoration: none; font-weight: 600;">WhatsApp</a>
</p>
<p style="margin: 12px 0 0 0; font-size: 11px; color: #9ca3af;">
&copy; {{ date('Y') }} Rezi App. Tous droits réservés.<br>
Abidjan, Côte d'Ivoire
</p>
</div>
</x-mail::footer>
</x-slot:footer>
</x-mail::layout>
