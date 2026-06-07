<x-app-layout>
    @section('title', 'Avis sur ' . $residence->title . ' - Rezi Studio Meublé Faya')

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Breadcrumb -->
            <nav class="mb-6" aria-label="Breadcrumb">
                <ol class="flex items-center gap-2 text-sm">
                    <li><a href="{{ route('home') }}" class="text-gray-500 hover:text-gray-700">Accueil</a></li>
                    <li class="text-gray-400">/</li>
                    <li><a href="{{ route('residences.show', $residence) }}" class="text-gray-500 hover:text-gray-700">{{ Str::limit($residence->title, 30) }}</a></li>
                    <li class="text-gray-400">/</li>
                    <li class="text-gray-900">Tous les avis</li>
                </ol>
            </nav>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Statistiques des avis -->
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-2xl shadow-sm p-6 sticky top-6">
                        <h2 class="text-lg font-semibold text-gray-900 mb-4">Résumé des avis</h2>
                        
                        <!-- Note moyenne globale -->
                        <div class="text-center mb-6">
                            <div class="text-4xl font-bold text-gray-900">
                                {{ number_format($stats['average'] ?? 0, 1) }}
                            </div>
                            <div class="flex items-center justify-center gap-1 my-2">
                                @for($i = 1; $i <= 5; $i++)
                                    <svg class="w-5 h-5 {{ $i <= round($stats['average'] ?? 0) ? 'text-yellow-400' : 'text-gray-300' }}" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                    </svg>
                                @endfor
                            </div>
                            <p class="text-sm text-gray-500">{{ $stats['total'] ?? 0 }} avis</p>
                        </div>

                        <!-- Détail par catégorie -->
                        @if(isset($stats['categories']))
                            <div class="space-y-3">
                                @foreach(['cleanliness' => 'Propreté', 'location' => 'Emplacement', 'value' => 'Rapport qualité/prix', 'communication' => 'Communication'] as $key => $label)
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm text-gray-600">{{ $label }}</span>
                                        <div class="flex items-center gap-2">
                                            <div class="w-24 h-2 bg-gray-200 rounded-full overflow-hidden">
                                                <div class="h-full bg-[#F16A00] rounded-full" style="width: {{ (($stats['categories'][$key] ?? 0) / 5) * 100 }}%"></div>
                                            </div>
                                            <span class="text-sm font-medium text-gray-900 w-8">{{ number_format($stats['categories'][$key] ?? 0, 1) }}</span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        <!-- Bouton écrire un avis -->
                        @auth
                            @if(!$userHasReviewed)
                                <a href="{{ route('reviews.create', $residence) }}" 
                                   class="mt-6 w-full inline-flex items-center justify-center gap-2 bg-[#CC5A00] text-white px-4 py-3 rounded-xl hover:bg-[#A34700] transition-colors">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                    Écrire un avis
                                </a>
                            @else
                                <p class="mt-6 text-sm text-gray-500 text-center">Vous avez déjà laissé un avis</p>
                            @endif
                        @else
                            <a href="{{ route('login') }}" class="mt-6 w-full inline-flex items-center justify-center gap-2 border border-[#CC5A00] text-[#CC5A00] px-4 py-3 rounded-xl hover:bg-[#FFF4EB] transition-colors">
                                Connectez-vous pour laisser un avis
                            </a>
                        @endauth
                    </div>
                </div>

                <!-- Liste des avis -->
                <div class="lg:col-span-2">
                    <h1 class="text-2xl font-bold text-gray-900 mb-6">Avis des locataires</h1>

                    @if($reviews->isEmpty())
                        <div class="bg-white rounded-2xl shadow-sm p-8 text-center">
                            <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                                </svg>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-900 mb-2">Aucun avis</h3>
                            <p class="text-gray-500">Soyez le premier à donner votre avis !</p>
                        </div>
                    @else
                        <div class="space-y-4">
                            @foreach($reviews as $review)
                                <div class="bg-white rounded-2xl shadow-sm p-6">
                                    <!-- Header -->
                                    <div class="flex items-start justify-between">
                                        <div class="flex items-center gap-3">
                                            @if($review->user->avatar)
                                                <img loading="lazy" src="{{ $review->user->getAvatarUrl() }}" alt="{{ $review->user->name }}" class="w-10 h-10 rounded-full object-cover">
                                            @else
                                                <div class="w-10 h-10 bg-linear-to-br from-[#F16A00] to-[#CC5A00] rounded-full flex items-center justify-center text-white font-semibold">
                                                    {{ strtoupper(substr($review->user->name, 0, 1)) }}
                                                </div>
                                            @endif
                                            <div>
                                                <h4 class="font-semibold text-gray-900">{{ $review->user->name }}</h4>
                                                <p class="text-sm text-gray-500">{{ $review->created_at->format('d M Y') }}</p>
                                            </div>
                                        </div>
                                        
                                        <!-- Note -->
                                        <div class="flex items-center gap-1">
                                            @for($i = 1; $i <= 5; $i++)
                                                <svg class="w-4 h-4 {{ $i <= $review->overall_rating ? 'text-yellow-400' : 'text-gray-300' }}" fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                                </svg>
                                            @endfor
                                        </div>
                                    </div>

                                    <!-- Commentaire -->
                                    @if($review->comment)
                                        <p class="mt-4 text-gray-700 leading-relaxed">{{ $review->comment }}</p>
                                    @endif

                                    <!-- Notes détaillées -->
                                    <div class="mt-4 flex flex-wrap gap-4 text-sm">
                                        <span class="text-gray-500">Propreté: <strong class="text-gray-900">{{ $review->rating_cleanliness }}/5</strong></span>
                                        <span class="text-gray-500">Emplacement: <strong class="text-gray-900">{{ $review->rating_location }}/5</strong></span>
                                        <span class="text-gray-500">Qualité/Prix: <strong class="text-gray-900">{{ $review->rating_value }}/5</strong></span>
                                        <span class="text-gray-500">Communication: <strong class="text-gray-900">{{ $review->rating_communication }}/5</strong></span>
                                    </div>

                                    <!-- Réponse du propriétaire -->
                                    @if($review->owner_response)
                                        <div class="mt-4 bg-gray-50 rounded-xl p-4 border-l-4 border-[#F16A00]">
                                            <div class="flex items-center gap-2 mb-2">
                                                <svg class="w-4 h-4 text-[#CC5A00]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
                                                </svg>
                                                <span class="text-sm font-semibold text-[#CC5A00]">Réponse du propriétaire</span>
                                            </div>
                                            <p class="text-sm text-gray-700">{{ $review->owner_response }}</p>
                                            <p class="text-xs text-gray-400 mt-2">{{ $review->owner_responded_at?->format('d M Y') }}</p>
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>

                        <!-- Pagination -->
                        <div class="mt-6">
                            {{ $reviews->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
