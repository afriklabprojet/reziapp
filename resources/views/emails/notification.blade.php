@component('mail::message')
# {{ $title }}

{{ $body }}

@if(isset($actionUrl) && $actionUrl)
@component('mail::button', ['url' => $actionUrl])
Voir les détails
@endcomponent
@endif

Merci de faire confiance à REZI !

Cordialement,<br>
L'équipe {{ config('app.name') }}

@component('mail::subcopy')
Si vous ne souhaitez plus recevoir ce type de notifications, vous pouvez modifier vos préférences dans [vos paramètres de notification]({{ route('notifications.preferences') }}).
@endcomponent
@endcomponent
