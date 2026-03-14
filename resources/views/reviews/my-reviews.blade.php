@extends('layouts.app')

@section('title', 'Mes avis')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-gray-900">Mes avis</h1>
        <p class="text-gray-500 mt-1">Gérez vos avis donnés et reçus</p>
    </div>

    <!-- Onglets -->
    <div class="border-b border-gray-200 mb-6">
        <nav class="flex gap-8">
            <a href="{{ route('reviews.my', ['type' => 'given']) }}" 
               class="pb-4 px-1 border-b-2 font-medium text-sm {{ $type === 'given' ? 'border-orange-500 text-orange-500' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                Avis donnés
            </a>
            @if(Auth::user()->isOwner())
                <a href="{{ route('reviews.my', ['type' => 'received']) }}" 
                   class="pb-4 px-1 border-b-2 font-medium text-sm {{ $type === 'received' ? 'border-orange-500 text-orange-500' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                    Avis reçus
                </a>
            @endif
        </nav>
    </div>

    @if($reviews->count() > 0)
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 divide-y divide-gray-100">
            @foreach($reviews as $review)
                <div class="p-6">
                    <div class="flex items-start gap-4">
                        <!-- Info résidence ou utilisateur -->
                        @if($type === 'given')
                            <a href="{{ route('residences.show', $review->residence) }}" class="shrink-0">
                                @if($review->residence->photos->count() > 0)
                                    <img loading="lazy" src="{{ storage_url($review->residence->photos->first()?->path) }}" 
                                         alt="{{ $review->residence->title }}"
                                         class="w-16 h-16 rounded-lg object-cover">
                                @else
                                    <div class="w-16 h-16 rounded-lg bg-gray-200 flex items-center justify-center">
                                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                                        </svg>
                                    </div>
                                @endif
                            </a>
                        @else
                            <a href="{{ route('profile.public', $review->user) }}" class="shrink-0">
                                <img loading="lazy" src="{{ $review->user->getAvatarUrl() }}" 
                                     alt="{{ $review->user->name }}"
                                     class="w-16 h-16 rounded-full object-cover">
                            </a>
                        @endif

                        <div class="flex-1 min-w-0">
                            <!-- En-tête -->
                            <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-2">
                                <div>
                                    @if($type === 'given')
                                        <a href="{{ route('residences.show', $review->residence) }}" 
                                           class="font-semibold text-gray-900 hover:text-orange-500">
                                            {{ $review->residence->title }}
                                        </a>
                                        <p class="text-sm text-gray-500">{{ $review->residence->commune }}</p>
                                    @else
                                        <a href="{{ route('profile.public', $review->user) }}" 
                                           class="font-semibold text-gray-900 hover:text-orange-500">
                                            {{ $review->user->name }}
                                        </a>
                                        <p class="text-sm text-gray-500">
                                            Avis sur 
                                            <a href="{{ route('residences.show', $review->residence) }}" class="hover:underline">
                                                {{ $review->residence->title }}
                                            </a>
                                        </p>
                                    @endif
                                </div>
                                <div class="text-right">
                                    <span class="text-sm text-gray-500">{{ $review->created_at->translatedFormat('d F Y') }}</span>
                                    <div class="flex items-center justify-end mt-1">
                                        @for($i = 1; $i <= 5; $i++)
                                            <svg class="w-4 h-4 {{ $i <= $review->rating ? 'text-yellow-400' : 'text-gray-200' }}" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                            </svg>
                                        @endfor
                                    </div>
                                </div>
                            </div>

                            <!-- Badges -->
                            <div class="flex flex-wrap gap-2 mt-2">
                                @if($review->is_verified)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-orange-100 text-orange-700">
                                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                        </svg>
                                        Vérifié
                                    </span>
                                @endif
                                @if($review->status === 'pending')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-800">
                                        En attente de modération
                                    </span>
                                @endif
                                @if($review->helpful_count > 0)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-700">
                                        {{ $review->helpful_count }} personne(s) ont trouvé cet avis utile
                                    </span>
                                @endif
                            </div>

                            <!-- Commentaire -->
                            <p class="text-gray-700 mt-3">{{ $review->comment }}</p>

                            <!-- Réponse du propriétaire (pour avis donnés) -->
                            @if($type === 'given' && $review->owner_response)
                                <div class="mt-4 pl-4 border-l-2 border-orange-200 bg-orange-50 rounded-r-lg p-3">
                                    <div class="flex items-center gap-2 mb-2">
                                        <span class="text-sm font-medium text-gray-900">Réponse du propriétaire</span>
                                    </div>
                                    <p class="text-sm text-gray-700">{{ $review->owner_response }}</p>
                                </div>
                            @endif

                            <!-- Formulaire de réponse (pour avis reçus) -->
                            @if($type === 'received' && !$review->owner_response)
                                <div class="mt-4" x-data="{ showForm: false }">
                                    <button @click="showForm = !showForm" 
                                            class="text-orange-500 hover:text-orange-600 text-sm font-medium">
                                        Répondre à cet avis
                                    </button>
                                    
                                    <form x-show="showForm" x-cloak
                                          action="{{ route('reviews.respond', $review) }}" 
                                          method="POST" 
                                          class="mt-4">
                                        @csrf
                                        <textarea name="owner_response" rows="3" required
                                                  class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500"
                                                  placeholder="Écrivez votre réponse..."></textarea>
                                        <div class="flex justify-end gap-2 mt-2">
                                            <button type="button" @click="showForm = false" 
                                                    class="px-4 py-2 text-gray-600 hover:text-gray-800 text-sm font-medium">
                                                Annuler
                                            </button>
                                            <button type="submit" 
                                                    class="px-4 py-2 bg-orange-500 hover:bg-orange-600 text-white text-sm font-medium rounded-lg">
                                                Publier
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            @elseif($type === 'received' && $review->owner_response)
                                <div class="mt-4 pl-4 border-l-2 border-orange-200 bg-orange-50 rounded-r-lg p-3">
                                    <div class="flex items-center gap-2 mb-2">
                                        <span class="text-sm font-medium text-gray-900">Votre réponse</span>
                                    </div>
                                    <p class="text-sm text-gray-700">{{ $review->owner_response }}</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Pagination -->
        <div class="mt-6">
            {{ $reviews->appends(['type' => $type])->links() }}
        </div>
    @else
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-12 text-center">
            <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
            </svg>
            <h3 class="text-lg font-medium text-gray-900 mb-2">
                @if($type === 'given')
                    Vous n'avez pas encore laissé d'avis
                @else
                    Vous n'avez pas encore reçu d'avis
                @endif
            </h3>
            <p class="text-gray-500 mb-4">
                @if($type === 'given')
                    Partagez votre expérience en laissant un avis sur une résidence que vous avez visitée.
                @else
                    Les avis de vos voyageurs apparaîtront ici.
                @endif
            </p>
            @if($type === 'given')
                <a href="{{ route('residences.index') }}" 
                   class="inline-flex items-center gap-2 px-4 py-2 bg-orange-500 hover:bg-orange-600 text-white font-medium rounded-lg transition">
                    Parcourir les résidences
                </a>
            @endif
        </div>
    @endif
</div>
@endsection
