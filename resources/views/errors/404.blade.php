<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Page non trouvée - REZI</title>
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
                404
            </div>
            <div class="absolute inset-0 flex items-center justify-center">
                <svg class="w-24 h-24 sm:w-32 sm:h-32 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
            </div>
        </div>
        
        {{-- Message --}}
        <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 text-center mb-3">
            Page introuvable
        </h1>
        <p class="text-gray-600 text-center max-w-md mb-8">
            Oups ! La page que vous recherchez semble avoir déménagé ou n'existe plus. 
            Peut-être cherchez-vous une résidence ?
        </p>
        
        {{-- Actions --}}
        <div class="flex flex-col sm:flex-row gap-4">
            <a href="{{ route('home') }}" class="inline-flex items-center justify-center gap-2 px-6 py-3 bg-orange-500 text-white font-semibold rounded-xl hover:bg-orange-600 transition-colors shadow-lg shadow-orange-500/30">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
                Retour à l'accueil
            </a>
            <a href="{{ route('residences.index') }}" class="inline-flex items-center justify-center gap-2 px-6 py-3 bg-white text-gray-700 font-semibold rounded-xl border border-gray-200 hover:bg-gray-50 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                Chercher une résidence
            </a>
        </div>
        
        {{-- Suggestions --}}
        <div class="mt-12 text-center">
            <p class="text-sm text-gray-500 mb-4">Liens utiles :</p>
            <div class="flex flex-wrap justify-center gap-4 text-sm">
                <a href="{{ route('residences.index') }}" class="text-orange-600 hover:text-orange-700">Résidences</a>
                <span class="text-gray-300">•</span>
                <a href="{{ route('residences.map') }}" class="text-orange-600 hover:text-orange-700">Carte</a>
                <span class="text-gray-300">•</span>
                <a href="{{ route('login') }}" class="text-orange-600 hover:text-orange-700">Connexion</a>
            </div>
        </div>
    </div>
</body>
</html>
