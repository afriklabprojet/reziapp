<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @php($cspNonce = \Illuminate\Support\Facades\Vite::cspNonce())

    {{-- Dark mode: apply immediately to prevent flash of wrong theme --}}
    <script nonce="{{ $cspNonce }}">
        (function() {
            var t = localStorage.getItem('theme');
            if (t === 'dark' || (!t && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark');
            }
        })();
    </script>

    {{-- SEO Meta (pages enfants peuvent pousser via @push('meta')) --}}
    <x-seo-meta :title="View::yieldContent('title', config('app.name', 'REZI'))" :description="View::yieldContent(
        'description',
        'Trouvez votre résidence meublée idéale en Afrique de l\'Ouest. Recherche géolocalisée, photos, contact direct avec les propriétaires.',
    )" />
    @stack('meta')

    <!-- PWA Meta Tags -->
    <meta name="theme-color" content="#F16A00">
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
    <meta name="msapplication-TileColor" content="#F16A00">
    <meta name="msapplication-config" content="/browserconfig.xml">

    <!-- Fonts: Plus Jakarta Sans (Airbnb Cereal / Circular fallback) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,400;0,500;0,600;0,700;1,400;1,500&display=swap" rel="stylesheet">

    <!-- Additional Styles -->
    @stack('styles')

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @production
    <!-- Microsoft Clarity -->
    <script type="text/javascript" nonce="{{ $cspNonce }}">
        (function(c,l,a,r,i,t,y){
            c[a]=c[a]||function(){(c[a].q=c[a].q||[]).push(arguments)};
            t=l.createElement(r);t.async=1;t.src="https://www.clarity.ms/tag/"+i;
            y=l.getElementsByTagName(r)[0];y.parentNode.insertBefore(t,y);
        })(window, document, "clarity", "script", "wfqugxj6q3");
    </script>
    @endproduction
</head>

<body class="font-sans antialiased">
    {{-- Skip to content (accessibilité) --}}
    <a href="#main-content"
        class="sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-4 focus:z-60 focus:bg-[#F16A00] focus:text-white focus:px-4 focus:py-2 focus:rounded-lg focus:shadow-lg focus:outline-none">
        Aller au contenu principal
    </a>

    <div class="min-h-screen bg-[#F2F2F2]/60 dark:bg-[#0F0F0F]">
        {{-- Header desktop classique --}}
        <div class="hidden md:block">
            @include('layouts.navigation')
        </div>

        {{-- Header mobile --}}
        @unless (request()->routeIs('home'))
            <div class="md:hidden">
                <x-mobile-header :title="$mobileTitle ?? null" />
            </div>
        @endunless

        <!-- Page Heading -->
        @isset($header)
            <header class="bg-white shadow hidden md:block">
                <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                    {{ $header }}
                </div>
            </header>
        @endisset

        <!-- Page Content -->
        <main id="main-content" class="pb-16 md:pb-0">
            @hasSection('content')
                @yield('content')
            @else
                {{ $slot ?? '' }}
            @endif
        </main>

        {{-- Navigation mobile --}}
        @unless (request()->routeIs('home'))
            <x-mobile-nav />
        @endunless

        {{-- Footer (hidden on mobile where bottom nav is visible) --}}
        <div class="hidden md:block">
            <x-footer />
        </div>

        {{-- Push Notifications --}}
        <x-push-notifications />
    </div>

    <!-- Additional Scripts -->
    @stack('scripts')

    <!-- Fallback global pour les images manquantes -->
    <script nonce="{{ $cspNonce }}">
    (function () {
        var PLACEHOLDER = '/images/placeholder-residence.jpg';
        var AVATAR_PLACEHOLDER = '/images/placeholder.jpg';
        function handleImgError(img) {
            if (img.dataset.fallback) return; // déjà en fallback, évite boucle infinie
            img.dataset.fallback = '1';
            var src = img.getAttribute('src') || '';
            // Ne pas remplacer les logos/icônes (SVG inline ou data URI)
            if (src.startsWith('data:') || src.endsWith('.svg')) return;
            // Choisir le bon placeholder selon le contexte
            var isAvatar = img.classList.contains('rounded-full') || img.closest('[data-avatar]');
            img.src = isAvatar ? AVATAR_PLACEHOLDER : PLACEHOLDER;
        }
        // Intercepte les images déjà chargées et celles futures
        document.addEventListener('error', function (e) {
            if (e.target && e.target.tagName === 'IMG') {
                handleImgError(e.target);
            }
        }, true);
    })();
    </script>

    {{-- Chatbot IA REZI — masqué sur mobile (évite collision avec la bottom nav) --}}
    <div class="hidden md:block">
        <x-chatbot
            :commune="request()->query('commune') ?: null"
        />
    </div>

    <!-- Service Worker Registration -->
    <script nonce="{{ $cspNonce }}">
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
