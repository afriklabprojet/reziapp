<x-app-layout>
    @section('title', $metaTitle ?? $content['title'] ?? "Guide Propriétaire - REZI")
    @section('description', $metaDescription ?? "Guide complet pour les propriétaires sur REZI.")

    <div class="min-h-screen bg-gray-50">
        {{-- Header --}}
        <div class="bg-linear-to-br from-orange-500 to-orange-600">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
                <nav class="text-sm text-orange-100 mb-4">
                    <a href="{{ route('home') }}" class="hover:text-white transition">Accueil</a>
                    <span class="mx-2">›</span>
                    <span class="text-white">Guide Propriétaire</span>
                </nav>
                <h1 class="text-3xl sm:text-4xl font-bold text-white">{{ $content['title'] ?? "Guide du Propriétaire" }}</h1>
                <p class="mt-3 text-orange-100">{{ $content['subtitle'] ?? "Tout ce que vous devez savoir pour réussir sur REZI" }}</p>
            </div>
        </div>

        {{-- Introduction --}}
        @if(!empty($content['introduction']))
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="bg-white rounded-2xl shadow-sm p-8">
                <p class="text-gray-600 leading-relaxed text-lg">{{ $content['introduction'] }}</p>
            </div>
        </div>
        @endif

        {{-- Steps --}}
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="space-y-8">
                @foreach($content['steps'] ?? [] as $stepIndex => $step)
                <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
                    {{-- Step Header --}}
                    <div class="bg-linear-to-r from-orange-500 to-orange-600 p-6">
                        <div class="flex items-start">
                            <span class="shrink-0 w-10 h-10 bg-white/20 text-white rounded-full flex items-center justify-center font-bold text-lg mr-4">{{ $stepIndex + 1 }}</span>
                            <div>
                                <h2 class="text-xl font-bold text-white">{{ $step['title'] }}</h2>
                                @if(!empty($step['description']))
                                <p class="text-orange-100 mt-1">{{ $step['description'] }}</p>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="p-6 space-y-6">
                        {{-- Substeps --}}
                        @if(!empty($step['substeps']))
                        <div class="space-y-3">
                            @foreach($step['substeps'] as $substep)
                            <div class="flex items-start">
                                <svg class="w-5 h-5 text-green-500 mr-3 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                <span class="text-gray-700">{{ $substep }}</span>
                            </div>
                            @endforeach
                        </div>
                        @endif

                        {{-- Tips --}}
                        @if(!empty($step['tips']))
                        <div class="bg-blue-50 rounded-xl p-4">
                            <h4 class="font-medium text-blue-900 mb-2 flex items-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                Conseils
                            </h4>
                            <ul class="space-y-1">
                                @foreach($step['tips'] as $tip)
                                <li class="text-blue-800 text-sm">• {{ $tip }}</li>
                                @endforeach
                            </ul>
                        </div>
                        @endif

                        {{-- Tools/Requirements --}}
                        @if(!empty($step['tools']))
                        <div class="bg-gray-50 rounded-xl p-4">
                            <h4 class="font-medium text-gray-900 mb-2">Outils nécessaires</h4>
                            <div class="flex flex-wrap gap-2">
                                @foreach($step['tools'] as $tool)
                                <span class="px-3 py-1 bg-white rounded-full text-sm text-gray-700 border">{{ $tool }}</span>
                                @endforeach
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        {{-- CTA --}}
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <div class="bg-linear-to-br from-orange-500 to-orange-600 rounded-2xl p-8 text-center">
                <h3 class="text-2xl font-bold text-white mb-2">Prêt à commencer ?</h3>
                <p class="text-orange-100 mb-6">Publiez votre première annonce dès maintenant</p>
                @auth
                <a href="{{ route('owner.residences.create') }}" class="inline-flex items-center px-6 py-3 bg-white text-orange-600 font-medium rounded-xl hover:bg-orange-50 transition">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    Créer une annonce
                </a>
                @else
                <a href="{{ route('register') }}" class="inline-flex items-center px-6 py-3 bg-white text-orange-600 font-medium rounded-xl hover:bg-orange-50 transition">
                    Créer un compte gratuitement
                </a>
                @endauth
            </div>
        </div>
    </div>
</x-app-layout>
