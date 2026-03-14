@extends('layouts.client', ['sidebarActive' => 'reviews'])

@section('title', 'Mes avis - REZI')

@section('client-content')
    {{-- En-tête --}}
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-gray-900">Mes avis</h1>
        <p class="text-gray-600">Retrouvez tous les avis que vous avez laissés</p>
    </div>

    {{-- Statistiques --}}
    <div class="grid grid-cols-3 gap-4 mb-8">
        <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-900">{{ $reviewStats['total'] }}</p>
                    <p class="text-sm text-gray-500">Avis publiés</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 bg-amber-100 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-amber-600 fill-current" viewBox="0 0 24 24">
                        <path
                            d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z" />
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-amber-600">{{ $reviewStats['avg_rating'] ?: '-' }}</p>
                    <p class="text-sm text-gray-500">Note moyenne</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-blue-600">{{ $reviewStats['with_response'] }}</p>
                    <p class="text-sm text-gray-500">Avec réponse</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Liste des avis --}}
    @if ($reviews->count() > 0)
        <div class="space-y-4">
            @foreach ($reviews as $review)
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="flex flex-col sm:flex-row">
                        {{-- Image résidence --}}
                        <a href="{{ route('residences.show', $review->residence) }}" class="sm:w-48 shrink-0">
                            <div class="aspect-video sm:aspect-square">
                                @if ($review->residence->photos->count() > 0)
                                    <img loading="lazy" src="{{ storage_url($review->residence->photos->first()?->path) }}"
                                        alt="{{ $review->residence->name }}" class="w-full h-full object-cover">
                                @else
                                    <div class="w-full h-full bg-gray-200 flex items-center justify-center">
                                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                                        </svg>
                                    </div>
                                @endif
                            </div>
                        </a>

                        {{-- Contenu --}}
                        <div class="flex-1 p-5">
                            <div class="flex items-start justify-between gap-4 mb-3">
                                <div>
                                    <a href="{{ route('residences.show', $review->residence) }}" class="block">
                                        <h3 class="font-semibold text-gray-900 hover:text-orange-500">
                                            {{ $review->residence->title }}</h3>
                                        <p class="text-sm text-gray-500">{{ $review->residence->commune }}</p>
                                    </a>
                                </div>
                                <div class="flex items-center gap-1 shrink-0">
                                    @for ($i = 1; $i <= 5; $i++)
                                        <svg class="w-5 h-5 {{ $i <= $review->rating ? 'text-amber-400' : 'text-gray-300' }} fill-current"
                                            viewBox="0 0 24 24">
                                            <path
                                                d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z" />
                                        </svg>
                                    @endfor
                                </div>
                            </div>

                            {{-- Commentaire --}}
                            @if ($review->comment)
                                <div class="mb-4">
                                    <p class="text-gray-700">{{ $review->comment }}</p>
                                    <p class="text-xs text-gray-400 mt-2">Publié {{ $review->created_at->diffForHumans() }}
                                    </p>
                                </div>
                            @endif

                            {{-- Réponse du propriétaire --}}
                            @if ($review->owner_response)
                                <div class="mt-4 p-4 bg-orange-50 rounded-lg border-l-4 border-orange-500">
                                    <div class="flex items-center gap-2 mb-2">
                                        <div class="w-8 h-8 rounded-full overflow-hidden bg-orange-100">
                                            @if ($review->residence->owner)
                                                <img loading="lazy" src="{{ $review->residence->owner->getAvatarUrl() }}"
                                                    alt="" class="w-full h-full object-cover">
                                            @endif
                                        </div>
                                        <div>
                                            <p class="text-sm font-medium text-gray-900">
                                                {{ $review->residence->owner->name ?? 'Propriétaire' }}</p>
                                            <p class="text-xs text-gray-500">
                                                {{ $review->owner_response_at ? $review->owner_response_at->diffForHumans() : '' }}
                                            </p>
                                        </div>
                                    </div>
                                    <p class="text-sm text-gray-700">{{ $review->owner_response }}</p>
                                </div>
                            @endif

                            {{-- Actions --}}
                            <div class="mt-4 flex items-center gap-3">
                                <a href="{{ route('residences.show', $review->residence) }}"
                                    class="inline-flex items-center px-4 py-2 bg-orange-500 hover:bg-orange-600 text-white text-sm font-medium rounded-lg transition">
                                    Voir la résidence
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Pagination --}}
        <div class="mt-6">
            {{ $reviews->links() }}
        </div>
    @else
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-12 text-center">
            <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                </svg>
            </div>
            <h3 class="text-lg font-medium text-gray-900 mb-2">Aucun avis publié</h3>
            <p class="text-gray-600 mb-6">Partagez votre expérience en laissant des avis sur les résidences visitées</p>
            <a href="{{ route('residences.index') }}"
                class="inline-flex items-center px-4 py-2 bg-orange-500 hover:bg-orange-600 text-white font-medium rounded-lg transition">
                Explorer les résidences
            </a>
        </div>
    @endif
@endsection
