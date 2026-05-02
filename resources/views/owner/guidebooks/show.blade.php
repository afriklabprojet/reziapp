@extends('layouts.owner')

@section('title', $guidebook->title . ' — REZI')

@section('owner-content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <a href="{{ route('owner.guidebooks.index') }}" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700 mb-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" /></svg>
                Guides
            </a>
            <h1 class="text-2xl font-bold text-gray-900">{{ $guidebook->title }}</h1>
            <p class="text-sm text-gray-500 mt-1">{{ $guidebook->residence?->name }}</p>
        </div>
        <div class="flex gap-2">
            <form action="{{ route('owner.guidebooks.toggle-publish', $guidebook) }}" method="POST">
                @csrf
                <button type="submit" class="inline-flex items-center gap-2 px-4 py-2.5 {{ $guidebook->is_published ? 'bg-[#fff0f3] text-[#b5083a] hover:bg-[#ffd1da]' : 'bg-green-50 text-green-700 hover:bg-green-100' }} font-semibold rounded-xl transition-all text-sm">
                    {{ $guidebook->is_published ? 'Dépublier' : 'Publier' }}
                </button>
            </form>
            <a href="{{ route('owner.guidebooks.edit', $guidebook) }}" class="inline-flex items-center gap-2 px-4 py-2.5 bg-gray-100 text-gray-700 font-semibold rounded-xl hover:bg-gray-200 transition-all text-sm">
                Modifier
            </a>
        </div>
    </div>

    @if($guidebook->is_published)
    <div class="bg-blue-50 border border-blue-200 rounded-2xl p-4">
        <p class="text-sm font-semibold text-blue-800 mb-1">🔗 Lien public du guide</p>
        <p class="text-xs text-blue-700 break-all">{{ $guidebook->public_url }}</p>
    </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            {{-- Infos WiFi --}}
            @if($guidebook->wifi_name)
            <div class="bg-white rounded-2xl border border-gray-100 p-6">
                <h2 class="text-lg font-bold text-gray-900 mb-4">📶 WiFi</h2>
                <p class="text-sm"><span class="text-gray-500">Réseau:</span> <span class="font-semibold">{{ $guidebook->wifi_name }}</span></p>
                <p class="text-sm"><span class="text-gray-500">Mot de passe:</span> <span class="font-semibold">{{ $guidebook->wifi_password }}</span></p>
            </div>
            @endif

            {{-- Sections --}}
            <div class="bg-white rounded-2xl border border-gray-100 p-6">
                <h2 class="text-lg font-bold text-gray-900 mb-4">Sections</h2>
                <div class="space-y-4">
                    @forelse($guidebook->sections as $section)
                    <div class="border border-gray-100 rounded-xl p-4">
                        <div class="flex items-start justify-between">
                            <div>
                                <p class="font-semibold text-gray-900">{{ $section->title }}</p>
                                <p class="text-sm text-gray-600 mt-1 whitespace-pre-wrap">{{ Str::limit($section->content, 200) }}</p>
                            </div>
                            <form action="{{ route('owner.guidebooks.remove-section', [$guidebook, $section]) }}" method="POST">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-red-500 hover:text-red-700">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                                </button>
                            </form>
                        </div>
                    </div>
                    @empty
                    <p class="text-sm text-gray-400 text-center py-4">Aucune section</p>
                    @endforelse
                </div>

                <form action="{{ route('owner.guidebooks.add-section', $guidebook) }}" method="POST" class="mt-6 pt-6 border-t border-gray-100 space-y-4">
                    @csrf
                    <p class="text-sm font-semibold text-gray-900">Ajouter une section</p>
                    <input type="text" name="title" placeholder="Titre de la section" required class="w-full rounded-xl border-gray-200 text-sm py-2 px-3">
                    <textarea name="content" rows="3" placeholder="Contenu..." required class="w-full rounded-xl border-gray-200 text-sm py-2 px-3"></textarea>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white font-semibold rounded-xl hover:bg-blue-700 text-sm">Ajouter</button>
                </form>
            </div>
        </div>

        <div class="space-y-6">
            {{-- Recommandations --}}
            <div class="bg-white rounded-2xl border border-gray-100 p-6">
                <h2 class="text-lg font-bold text-gray-900 mb-4">📍 Recommandations</h2>
                <div class="space-y-3">
                    @forelse($guidebook->recommendations as $reco)
                    <div class="text-sm">
                        <p class="font-semibold text-gray-900">{{ $reco->name }}</p>
                        <p class="text-xs text-gray-500">{{ \App\Models\GuidebookRecommendation::CATEGORIES[$reco->category] ?? $reco->category }} · {{ $reco->distance }}</p>
                    </div>
                    @empty
                    <p class="text-xs text-gray-400">Aucune recommandation</p>
                    @endforelse
                </div>

                <form action="{{ route('owner.guidebooks.add-recommendation', $guidebook) }}" method="POST" class="mt-4 pt-4 border-t border-gray-100 space-y-3">
                    @csrf
                    <input type="text" name="name" placeholder="Nom du lieu" required class="w-full rounded-xl border-gray-200 text-sm py-2 px-3">
                    <select name="category" required class="w-full rounded-xl border-gray-200 text-sm py-2 px-3">
                        @foreach(\App\Models\GuidebookRecommendation::CATEGORIES as $k => $l)
                        <option value="{{ $k }}">{{ $l }}</option>
                        @endforeach
                    </select>
                    <input type="text" name="distance" placeholder="Ex: 5 min à pied" class="w-full rounded-xl border-gray-200 text-sm py-2 px-3">
                    <button type="submit" class="w-full px-4 py-2 bg-gray-900 text-white font-semibold rounded-xl hover:bg-gray-800 text-sm">Ajouter</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
