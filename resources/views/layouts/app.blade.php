<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- SEO Meta (pages enfants peuvent pousser via @push('meta')) --}}
    <x-seo-meta :title="View::yieldContent('title', config('app.name', 'REZI'))" :description="View::yieldContent(
        'description',
        'Trouvez votre résidence meublée idéale en Afrique de l\'Ouest. Recherche géolocalisée, photos, contact direct avec les propriétaires.',
    )" />
    @stack('meta')

    <!-- PWA Meta Tags -->
    <meta name="theme-color" content="#F7931E">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="REZI">
    <link rel="manifest" href="/manifest.json">
    <link rel="apple-touch-icon" sizes="180x180" href="/images/icons/apple-touch-icon.png">
    <link rel="icon" type="image/svg+xml" href="/images/icons/favicon.svg">
    <link rel="icon" type="image/png" sizes="32x32" href="/images/icons/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/images/icons/favicon-16x16.png">
    <link rel="shortcut icon" href="/favicon.ico">
    <meta name="msapplication-TileColor" content="#F7931E">
    <meta name="msapplication-config" content="/browserconfig.xml">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

    <!-- Additional Styles -->
    @stack('styles')

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="font-sans antialiased">
    {{-- Skip to content (accessibilité) --}}
    <a href="#main-content"
        class="sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-4 focus:z-60 focus:bg-orange-500 focus:text-white focus:px-4 focus:py-2 focus:rounded-lg focus:shadow-lg focus:outline-none">
        Aller au contenu principal
    </a>

    <div class="min-h-screen bg-gray-100">
        {{-- Header desktop classique --}}
        <div class="hidden md:block">
            @include('layouts.navigation')
        </div>

        {{-- Header mobile --}}
        <div class="md:hidden">
            <x-mobile-header :title="$mobileTitle ?? null" />
        </div>

        <!-- Page Heading -->
        @isset($header)
            <header class="bg-white shadow hidden md:block">
                <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                    {{ $header }}
                </div>
            </header>
        @endisset

        <!-- Page Content -->
        <main id="main-content" class="pb-20 md:pb-0">
            @hasSection('content')
                @yield('content')
            @else
                {{ $slot ?? '' }}
            @endif
        </main>

        {{-- Navigation mobile --}}
        <x-mobile-nav />

        {{-- Footer (hidden on mobile where bottom nav is visible) --}}
        <div class="hidden md:block">
            <x-footer />
        </div>

        {{-- Push Notifications --}}
        <x-push-notifications />
    </div>

    <!-- Additional Scripts -->
    @stack('scripts')

    <!-- Service Worker Registration -->
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function() {
                navigator.serviceWorker.register('/sw.js')
                    .then(function(registration) {
                        console.log('ServiceWorker enregistré avec succès:', registration.scope);
                    })
                    .catch(function(error) {
                        console.log('Échec de l\'enregistrement du ServiceWorker:', error);
                    });
            });
        }
    </script>
</body>

</html>
