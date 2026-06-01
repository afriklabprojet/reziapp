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
                <div class="w-12 h-12 bg-[#FFE7D1] rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-[#F16A00]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                                        <h3 class="font-semibold text-gray-900 hover:text-[#F16A00]">
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
                                <div class="mt-4 p-4 bg-[#FFF4EB] rounded-lg border-l-4 border-[#F16A00]">
                                    <div class="flex items-center gap-2 mb-2">
                                        <div class="w-8 h-8 rounded-full overflow-hidden bg-[#FFE7D1]">
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
                            <div class="mt-4 flex items-center gap-3 flex-wrap">
                                <a href="{{ route('residences.show', $review->residence) }}"
                                    class="inline-flex items-center px-4 py-2 bg-[#F16A00] hover:bg-[#CC5A00] text-white text-sm font-medium rounded-lg transition">
                                    Voir la résidence
                                </a>

                                @if (! $review->owner_response)
                                    <button type="button"
                                        x-data
                                        @click="$dispatch('open-review-edit', {
                                            reviewId: {{ $review->id }},
                                            rating: {{ $review->rating }},
                                            comment: @js($review->comment ?? ''),
                                            actionUrl: '{{ route('client.reviews.update', $review) }}'
                                        })"
                                        class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition">
                                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z" />
                                        </svg>
                                        Modifier
                                    </button>

                                    <form action="{{ route('client.reviews.delete', $review) }}" method="POST"
                                          onsubmit="return confirm('Supprimer définitivement cet avis ?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                            class="inline-flex items-center px-3 py-2 text-sm font-medium text-red-600 bg-red-50 hover:bg-red-100 rounded-lg transition">
                                            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                            Supprimer
                                        </button>
                                    </form>
                                @else
                                    <span class="text-xs text-gray-400 italic">Modification impossible — réponse propriétaire</span>
                                @endif
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
                class="inline-flex items-center px-4 py-2 bg-[#F16A00] hover:bg-[#CC5A00] text-white font-medium rounded-lg transition">
                Explorer les résidences
            </a>
        </div>
    @endif
@endsection

{{-- Modal édition avis --}}
<div
    x-data="{
        open: false,
        reviewId: null,
        rating: 5,
        comment: '',
        actionUrl: '',
        submitting: false,
    }"
    @open-review-edit.window="
        reviewId = $event.detail.reviewId;
        rating = $event.detail.rating;
        comment = $event.detail.comment;
        actionUrl = $event.detail.actionUrl;
        open = true;
    "
    x-show="open"
    x-cloak
    class="fixed inset-0 z-50 flex items-center justify-center p-4"
    style="display: none;"
>
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="open = false"></div>

    <div class="relative w-full max-w-lg bg-white rounded-2xl shadow-xl p-6 z-10" @click.stop>
        <div class="flex items-start justify-between mb-5">
            <div>
                <h3 class="text-base font-semibold text-gray-900">Modifier mon avis</h3>
                <p class="text-sm text-gray-500 mt-0.5">L'avis repassera en modération après modification.</p>
            </div>
            <button @click="open = false" class="text-gray-400 hover:text-gray-600 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <form :action="actionUrl" method="POST" @submit="submitting = true">
            @csrf
            @method('PATCH')

            {{-- Note --}}
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Note <span class="text-red-500">*</span></label>
                <div class="flex gap-1" x-data>
                    @for ($s = 1; $s <= 5; $s++)
                        <button type="button"
                            @click="$dispatch('set-rating', { value: {{ $s }} })"
                            @set-rating.window="rating = $event.detail.value"
                            class="focus:outline-none transition-transform hover:scale-110">
                            <svg class="w-8 h-8 transition-colors"
                                :class="rating >= {{ $s }} ? 'text-amber-400 fill-current' : 'text-gray-300 fill-current'"
                                viewBox="0 0 24 24">
                                <path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z" />
                            </svg>
                        </button>
                    @endfor
                </div>
                <input type="hidden" name="rating" :value="rating">
            </div>

            {{-- Commentaire --}}
            <div class="mb-5">
                <label class="block text-sm font-medium text-gray-700 mb-1">Commentaire</label>
                <textarea
                    name="comment"
                    x-model="comment"
                    rows="4"
                    placeholder="Décrivez votre expérience… (min. 10 caractères)"
                    class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm placeholder-gray-400 focus:ring-2 focus:ring-[#FFD0A3] focus:border-[#FF8A1F] resize-none"
                    minlength="10"
                    maxlength="2000"
                ></textarea>
                <p class="text-xs text-gray-400 mt-1" x-text="comment.length + '/2000 caractères'"></p>
            </div>

            @error('rating')  <p class="text-xs text-red-500 mb-2">{{ $message }}</p> @enderror
            @error('comment') <p class="text-xs text-red-500 mb-2">{{ $message }}</p> @enderror

            <div class="flex gap-3">
                <button type="button" @click="open = false"
                    class="flex-1 px-4 py-2.5 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-xl transition">
                    Annuler
                </button>
                <button type="submit"
                    :disabled="submitting"
                    class="flex-1 px-4 py-2.5 text-sm font-medium text-white bg-[#F16A00] hover:bg-[#CC5A00] disabled:bg-[#FFB46F] disabled:cursor-not-allowed rounded-xl transition">
                    <span x-show="!submitting">Enregistrer</span>
                    <span x-show="submitting">Mise à jour…</span>
                </button>
            </div>
        </form>
    </div>
</div>
