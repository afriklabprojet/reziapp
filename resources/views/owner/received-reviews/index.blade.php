@extends('layouts.owner')

@section('title', 'Avis reçus — REZI')

@section('owner-content')
<div class="space-y-6" x-data="{ respondingTo: null, responseText: {} }">

    {{-- En-tête --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Avis reçus</h1>
            <p class="text-sm text-gray-500 mt-1">Avis laissés par vos locataires sur vos résidences</p>
        </div>
    </div>

    {{-- Statistiques --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div class="bg-white rounded-2xl border border-gray-100 p-4 text-center">
            <p class="text-2xl font-bold text-gray-900">{{ $stats['total'] }}</p>
            <p class="text-xs text-gray-500 mt-1">Total</p>
        </div>
        <div class="bg-white rounded-2xl border border-gray-100 p-4 text-center">
            <p class="text-2xl font-bold text-yellow-500">{{ $stats['pending'] }}</p>
            <p class="text-xs text-gray-500 mt-1">Sans réponse</p>
        </div>
        <div class="bg-white rounded-2xl border border-gray-100 p-4 text-center">
            <p class="text-2xl font-bold text-green-600">{{ $stats['responded'] }}</p>
            <p class="text-xs text-gray-500 mt-1">Avec réponse</p>
        </div>
        <div class="bg-white rounded-2xl border border-gray-100 p-4 text-center">
            <p class="text-2xl font-bold text-[#F16A00]">{{ number_format($stats['avg_rating'] ?? 0, 1) }}</p>
            <p class="text-xs text-gray-500 mt-1">Note moyenne</p>
        </div>
    </div>

    @if(session('success'))
        <div class="bg-green-50 border border-green-200 rounded-xl p-4 text-sm text-green-700 font-medium">
            {{ session('success') }}
        </div>
    @endif

    {{-- Filtres --}}
    <div class="bg-white rounded-2xl border border-gray-100 p-4">
        <div class="flex flex-wrap gap-2">
            @foreach(['all' => 'Tous', 'pending' => 'Sans réponse', 'responded' => 'Avec réponse'] as $key => $label)
                <a href="{{ route('owner.received-reviews.index', ['filter' => $key]) }}"
                   class="px-4 py-2 rounded-xl text-sm font-medium transition-colors {{ $filter === $key ? 'bg-gray-900 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                    {{ $label }}
                    @if($key === 'pending' && $stats['pending'] > 0)
                        <span class="ml-1 inline-flex items-center justify-center w-5 h-5 rounded-full text-xs {{ $filter === $key ? 'bg-white/20 text-white' : 'bg-[#F16A00] text-white' }}">{{ $stats['pending'] }}</span>
                    @endif
                </a>
            @endforeach
        </div>
    </div>

    {{-- Liste des avis --}}
    <div class="space-y-4">
        @forelse($reviews as $review)
            <div class="bg-white rounded-2xl border {{ is_null($review->owner_response) ? 'border-yellow-200' : 'border-gray-100' }} p-5 sm:p-6">
                {{-- En-tête avis --}}
                <div class="flex items-start justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center shrink-0 overflow-hidden">
                            @if($review->user->profile_photo || $review->user->avatar)
                                <img src="{{ $review->user->getAvatarUrl() }}" alt="{{ $review->user->name ?? '' }}" class="w-full h-full object-cover">
                            @else
                                <span class="text-sm font-semibold text-gray-600">{{ strtoupper(substr($review->user->name, 0, 1)) }}</span>
                            @endif
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-gray-900">{{ $review->user->name }}</p>
                            <p class="text-xs text-gray-500">{{ $review->created_at->format('d M Y') }} · <span class="font-medium text-gray-700">{{ $review->residence->name }}</span></p>
                        </div>
                    </div>
                    {{-- Note --}}
                    <div class="flex items-center gap-1 shrink-0">
                        @for($i = 1; $i <= 5; $i++)
                            <svg class="w-4 h-4 {{ $i <= $review->rating ? 'text-yellow-400' : 'text-gray-200' }}" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                            </svg>
                        @endfor
                        <span class="ml-1 text-sm font-bold text-gray-700">{{ number_format($review->rating, 1) }}</span>
                    </div>
                </div>

                {{-- Commentaire --}}
                @if($review->comment)
                    <p class="mt-3 text-sm text-gray-700 leading-relaxed">{{ $review->comment }}</p>
                @endif

                {{-- Réponse existante --}}
                @if($review->owner_response)
                    <div class="mt-4 bg-gray-50 rounded-xl p-4 border-l-4 border-[#FF8A1F]">
                        <p class="text-xs font-semibold text-gray-500 mb-1">Votre réponse · {{ $review->owner_response_at?->format('d M Y') }}</p>
                        <p class="text-sm text-gray-700">{{ $review->owner_response }}</p>
                        <button @click="respondingTo = {{ $review->id }}" class="mt-2 text-xs text-[#CC5A00] hover:text-[#A34700] font-medium">Modifier</button>
                    </div>
                @endif

                {{-- Formulaire de réponse --}}
                @if(is_null($review->owner_response))
                    <div class="mt-4">
                        <button @click="respondingTo = (respondingTo === {{ $review->id }} ? null : {{ $review->id }})"
                                class="inline-flex items-center gap-2 px-4 py-2 bg-[#F16A00] hover:bg-[#CC5A00] text-white text-sm font-semibold rounded-xl transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6 6-6"/>
                            </svg>
                            Répondre à cet avis
                        </button>
                    </div>
                @endif

                {{-- Zone de saisie de la réponse --}}
                <div x-show="respondingTo === {{ $review->id }}" x-cloak class="mt-4">
                    <form method="POST" action="{{ route('owner.received-reviews.respond', $review) }}">
                        @csrf
                        <textarea
                            name="owner_response"
                            rows="4"
                            placeholder="Rédigez une réponse professionnelle et courtoise (min. 10 caractères)..."
                            class="w-full rounded-xl border border-gray-200 text-sm p-3 focus:ring-2 focus:ring-[#FFD0A3] focus:border-[#FF8A1F] outline-none resize-none"
                            required
                            minlength="10"
                            maxlength="1000">{{ $review->owner_response }}</textarea>
                        <div class="flex items-center gap-3 mt-3">
                            <button type="submit" class="px-5 py-2 bg-gray-900 hover:bg-gray-800 text-white text-sm font-semibold rounded-xl transition-colors">
                                Publier la réponse
                            </button>
                            <button type="button" @click="respondingTo = null" class="px-4 py-2 text-sm text-gray-500 hover:text-gray-700 font-medium">
                                Annuler
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        @empty
            <div class="bg-white rounded-2xl border border-gray-100 p-12 text-center">
                <svg class="w-12 h-12 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 0 1 1.04 0l2.125 5.111a.563.563 0 0 0 .475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 0 0-.182.557l1.285 5.385a.562.562 0 0 1-.84.61l-4.725-2.885a.562.562 0 0 0-.586 0L6.982 20.54a.562.562 0 0 1-.84-.61l1.285-5.386a.562.562 0 0 0-.182-.557l-4.204-3.602a.562.562 0 0 1 .321-.988l5.518-.442a.563.563 0 0 0 .475-.345L11.48 3.5Z" />
                </svg>
                <p class="text-gray-500 font-medium">Aucun avis trouvé</p>
                <p class="text-sm text-gray-400 mt-1">Les avis de vos locataires apparaîtront ici</p>
            </div>
        @endforelse
    </div>

    {{-- Pagination --}}
    @if($reviews->hasPages())
        <div class="mt-4">
            {{ $reviews->links() }}
        </div>
    @endif

</div>
@endsection
