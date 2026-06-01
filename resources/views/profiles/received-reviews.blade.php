@extends('layouts.app')

@section('title', 'Avis reçus — ' . $user->name)

@section('content')
<div class="max-w-4xl mx-auto px-4 py-8">
    <a href="{{ route('profile.public', $user) }}" class="inline-flex items-center text-sm text-gray-500 hover:text-gray-700 mb-6">
        <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        Profil de {{ $user->name }}
    </a>

    <h1 class="text-2xl font-bold text-gray-900 mb-6">Avis reçus par {{ $user->name }}</h1>

    @if($reviews->isEmpty())
    <div class="bg-white rounded-xl shadow-sm border p-12 text-center">
        <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
        <h3 class="text-lg font-medium text-gray-900 mb-2">Aucun avis reçu</h3>
        <p class="text-gray-600">Cet utilisateur n'a pas encore reçu d'avis.</p>
    </div>
    @else
    <div class="space-y-4">
        @foreach($reviews as $review)
        <div class="bg-white rounded-xl shadow-sm border p-6">
            <div class="flex items-start justify-between mb-3">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-[#FFE7D1] rounded-full flex items-center justify-center">
                        <span class="text-[#CC5A00] font-bold">{{ substr($review->user->name ?? '?', 0, 1) }}</span>
                    </div>
                    <div>
                        <p class="font-medium text-gray-900">{{ $review->user->name ?? 'Utilisateur' }}</p>
                        <p class="text-xs text-gray-500">{{ $review->created_at->diffForHumans() }}</p>
                    </div>
                </div>
                <div class="flex items-center">
                    @for($i = 1; $i <= 5; $i++)
                    <svg class="w-4 h-4 {{ $i <= $review->rating ? 'text-yellow-400' : 'text-gray-300' }}" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                    @endfor
                </div>
            </div>
            <p class="text-gray-700">{{ $review->comment }}</p>
            @if($review->residence)
            <a href="{{ route('residences.show', $review->residence) }}" class="inline-flex items-center text-sm text-[#CC5A00] hover:text-[#A34700] mt-3">
                {{ $review->residence->title }} →
            </a>
            @endif
        </div>
        @endforeach
    </div>
    {{ $reviews->links() }}
    @endif
</div>
@endsection
