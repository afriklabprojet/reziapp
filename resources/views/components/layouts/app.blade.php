{{--
    Composant <x-app-layout> unifié
    Délègue au layout principal layouts.app pour garantir :
    - Header mobile dédié + navigation mobile bottom
    - PWA meta tags + Service Worker
    - Analytics + Push Notifications

    Les vues utilisant <x-app-layout> héritent automatiquement
    de toutes les fonctionnalités du layout principal.
--}}
@extends('layouts.app')

@if (isset($meta))
    @push('meta')
        {{ $meta }}
    @endpush
@endif

@section('content')
    {{ $slot }}
@endsection
