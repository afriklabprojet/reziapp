@extends('layouts.app')

@section('title', 'Avis de ' . $review->user->name)

@section('content')
<div class="max-w-3xl mx-auto px-4 py-8">
    <a href="{{ url()->previous() }}" class="inline-flex items-center text-sm text-gray-500 hover:text-gray-700 mb-6">
        <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        Retour
    </a>

    <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
        <!-- Review Header -->
        <div class="p-6 border-b">
            <div class="flex items-start space-x-4">
                <div class="w-12 h-12 bg-[#FFE7D1] rounded-full flex items-center justify-center shrink-0">
                    <span class="text-[#CC5A00] font-bold text-lg">{{ substr($review->user->name, 0, 1) }}</span>
                </div>
                <div class="flex-1">
                    <div class="flex items-center justify-between">
                        <h2 class="font-semibold text-gray-900">{{ $review->user->name }}</h2>
                        <span class="text-sm text-gray-500">{{ $review->created_at->diffForHumans() }}</span>
                    </div>
                    <!-- Stars -->
                    <div class="flex items-center mt-1">
                        @for($i = 1; $i <= 5; $i++)
                        <svg class="w-5 h-5 {{ $i <= $review->rating ? 'text-yellow-400' : 'text-gray-300' }}" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                        </svg>
                        @endfor
                        <span class="ml-2 text-sm font-medium text-gray-600">{{ $review->rating }}/5</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Residence Info -->
        @if($review->residence)
        <div class="p-6 bg-gray-50 border-b">
            <a href="{{ route('residences.show', $review->residence) }}" class="flex items-center space-x-3 hover:opacity-80 transition">
                @if($review->residence->mainPhoto)
                <img loading="lazy" src="{{ storage_url($review->residence->mainPhoto->path) }}" alt="{{ $review->residence->title }}" class="w-16 h-16 object-cover rounded-lg">
                @endif
                <div>
                    <p class="font-medium text-gray-900">{{ $review->residence->title }}</p>
                    <p class="text-sm text-gray-500">{{ $review->residence->commune?->name ?? $review->residence->address }}</p>
                </div>
            </a>
        </div>
        @endif

        <!-- Review Content -->
        <div class="p-6">
            @if($review->title)
            <h3 class="text-lg font-semibold text-gray-900 mb-2">{{ $review->title }}</h3>
            @endif
            <p class="text-gray-700 leading-relaxed whitespace-pre-line">{{ $review->comment }}</p>

            <!-- Sub-ratings -->
            @if($review->cleanliness_rating || $review->location_rating || $review->value_rating || $review->communication_rating)
            <div class="mt-6 grid grid-cols-2 gap-4">
                @foreach([
                    'cleanliness_rating' => 'Propreté',
                    'location_rating' => 'Emplacement',
                    'value_rating' => 'Rapport qualité/prix',
                    'communication_rating' => 'Communication'
                ] as $field => $label)
                    @if($review->$field)
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600">{{ $label }}</span>
                        <div class="flex items-center">
                            <span class="text-sm font-medium text-gray-900 mr-1">{{ $review->$field }}</span>
                            <svg class="w-4 h-4 text-yellow-400" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                        </div>
                    </div>
                    @endif
                @endforeach
            </div>
            @endif

            <!-- Helpful votes -->
            <div class="mt-6 flex items-center space-x-4 pt-4 border-t">
                <button onclick="toggleHelpful({{ $review->id }})" class="inline-flex items-center text-sm text-gray-500 hover:text-[#CC5A00]">
                    <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017c-.163 0-.326-.02-.485-.06L7 20m7-10V5a2 2 0 00-2-2h-.095c-.5 0-.905.405-.905.905 0 .714-.211 1.412-.608 2.006L7 11v9m7-10h-2M7 20H5a2 2 0 01-2-2v-6a2 2 0 012-2h2.5"/></svg>
                    Utile ({{ $review->helpful_votes_count ?? 0 }})
                </button>
                <button onclick="openReportModal({{ $review->id }})" class="inline-flex items-center text-sm text-gray-500 hover:text-red-500">
                    <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9"/></svg>
                    Signaler
                </button>
            </div>
        </div>

        <!-- Owner Response -->
        @if($review->owner_response)
        <div class="p-6 bg-blue-50 border-t">
            <div class="flex items-start space-x-3">
                <svg class="w-6 h-6 text-blue-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/></svg>
                <div>
                    <p class="text-sm font-medium text-blue-900 mb-1">Réponse du propriétaire</p>
                    <p class="text-sm text-blue-800">{{ $review->owner_response }}</p>
                    @if($review->responded_at)
                    <p class="text-xs text-blue-600 mt-2">{{ $review->responded_at->diffForHumans() }}</p>
                    @endif
                </div>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
