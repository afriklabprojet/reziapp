@extends('layouts.app')

@section('title', 'Avis donnés — ' . $user->name)

@section('content')
<div class="max-w-4xl mx-auto px-4 py-8">
    <a href="{{ route('profile.public', $user) }}" class="inline-flex items-center text-sm text-gray-500 hover:text-gray-700 mb-6">
        <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        Profil de {{ $user->name }}
    </a>

    <h1 class="text-2xl font-bold text-gray-900 mb-6">Avis donnés par {{ $user->name }}</h1>

    @if($reviews->isEmpty())
    <div class="bg-white rounded-xl shadow-sm border p-12 text-center">
        <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
        <h3 class="text-lg font-medium text-gray-900 mb-2">Aucun avis donné</h3>
        <p class="text-gray-600">Cet utilisateur n'a pas encore laissé d'avis.</p>
    </div>
    @else
    <div class="space-y-4">
        @foreach($reviews as $review)
        <div class="bg-white rounded-xl shadow-sm border p-6">
            <div class="flex items-start justify-between mb-3">
                <div>
                    @if($review->residence)
                    <a href="{{ route('residences.show', $review->residence) }}" class="font-medium text-gray-900 hover:text-[#CC5A00]">{{ $review->residence->title }}</a>
                    @endif
                    <p class="text-xs text-gray-500 mt-1">{{ $review->created_at->diffForHumans() }}</p>
                </div>
                <div class="flex items-center">
                    @for($i = 1; $i <= 5; $i++)
                    <svg class="w-4 h-4 {{ $i <= $review->rating ? 'text-yellow-400' : 'text-gray-300' }}" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                    @endfor
                </div>
            </div>
            <p class="text-gray-700">{{ $review->comment }}</p>
        </div>
        @endforeach
    </div>
    {{ $reviews->links() }}
    @endif
</div>
@endsection
