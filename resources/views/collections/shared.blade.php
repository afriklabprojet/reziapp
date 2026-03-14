@extends('layouts.app')

@section('title', $collection->name . ' — Collection partagée')

@section('content')
<div class="max-w-6xl mx-auto px-4 py-8">
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-gray-900">{{ $collection->name }}</h1>
        @if($collection->description)
        <p class="text-gray-600 mt-1">{{ $collection->description }}</p>
        @endif
        <p class="text-sm text-gray-400 mt-2">Collection partagée par {{ $collection->user->name ?? 'un utilisateur' }} — {{ $favorites->count() }} résidence(s)</p>
    </div>

    @if($favorites->isEmpty())
    <div class="bg-white rounded-xl shadow-sm border p-12 text-center">
        <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
        <h3 class="text-lg font-medium text-gray-900 mb-2">Collection vide</h3>
        <p class="text-gray-600">Cette collection ne contient pas encore de résidences.</p>
    </div>
    @else
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($favorites as $favorite)
            @if($favorite->residence)
            <x-residence-card :residence="$favorite->residence" />
            @endif
        @endforeach
    </div>
    @endif
</div>
@endsection
