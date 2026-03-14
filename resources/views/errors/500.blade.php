<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Erreur serveur - REZI</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
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
                500
            </div>
            <div class="absolute inset-0 flex items-center justify-center">
                <svg class="w-24 h-24 sm:w-32 sm:h-32 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
            </div>
        </div>
        
        {{-- Message --}}
        <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 text-center mb-3">
            Oups ! Une erreur s'est produite
        </h1>
        <p class="text-gray-600 text-center max-w-md mb-8">
            Notre équipe technique a été notifiée et travaille à résoudre le problème. 
            Veuillez réessayer dans quelques instants.
        </p>
        
        {{-- Actions --}}
        <div class="flex flex-col sm:flex-row gap-4">
            <button onclick="location.reload()" class="inline-flex items-center justify-center gap-2 px-6 py-3 bg-orange-500 text-white font-semibold rounded-xl hover:bg-orange-600 transition-colors shadow-lg shadow-orange-500/30">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                Réessayer
            </button>
            <a href="{{ route('home') }}" class="inline-flex items-center justify-center gap-2 px-6 py-3 bg-white text-gray-700 font-semibold rounded-xl border border-gray-200 hover:bg-gray-50 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
                Retour à l'accueil
            </a>
        </div>
        
        {{-- Status indicator --}}
        <div class="mt-12 text-center">
            <div class="inline-flex items-center gap-2 px-4 py-2 bg-amber-50 text-amber-700 rounded-full text-sm">
                <span class="w-2 h-2 bg-amber-500 rounded-full animate-pulse"></span>
                Nous travaillons sur le problème
            </div>
        </div>
    </div>
</body>
</html>
