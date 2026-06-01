<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Accès refusé - REZI</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css'])
</head>
<body class="font-sans antialiased bg-gray-50">
    <div class="min-h-screen flex flex-col items-center justify-center px-4">
        {{-- Logo --}}
        <a href="{{ route('home') }}" class="mb-8">
            <img loading="lazy" src="{{ asset('images/logo-rezi.png') }}" alt="REZI" class="h-12 w-auto">
        </a>

        {{-- Error illustration --}}
        <div class="relative mb-8">
            <div class="text-[150px] sm:text-[200px] font-black text-gray-100 leading-none select-none">
                403
            </div>
            <div class="absolute inset-0 flex items-center justify-center">
                <svg class="w-24 h-24 sm:w-32 sm:h-32 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                </svg>
            </div>
        </div>

        {{-- Message --}}
        <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 text-center mb-3">
            Accès refusé
        </h1>
        <p class="text-gray-600 text-center max-w-md mb-8">
            Vous n'avez pas la permission d'accéder à cette page.
            Contactez-nous si vous pensez qu'il s'agit d'une erreur.
        </p>

        {{-- Actions --}}
        <div class="flex flex-col sm:flex-row gap-4">
            <a href="{{ route('home') }}" class="inline-flex items-center justify-center gap-2 px-6 py-3 bg-[#F16A00] text-white font-semibold rounded-xl hover:bg-[#CC5A00] transition-colors shadow-lg shadow-none">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
                Retour à l'accueil
            </a>
            <button onclick="history.back()" class="inline-flex items-center justify-center gap-2 px-6 py-3 bg-white text-gray-700 font-semibold rounded-xl border border-gray-200 hover:bg-gray-50 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Page précédente
            </button>
        </div>

        {{-- Help link --}}
        <div class="mt-12 text-center">
            <p class="text-sm text-gray-500">
                Besoin d'aide ?
                <a href="mailto:{{ config('rezi.company.email') }}" class="text-[#CC5A00] hover:text-[#A34700] font-medium">Contactez-nous</a>
            </p>
        </div>
    </div>
</body>
</html>
