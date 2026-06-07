@extends('layouts.owner')

@section('title', 'Documents — Rezi Studio Meublé Faya')

@section('owner-content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Coffre-fort Documents</h1>
            <p class="text-sm text-gray-500 mt-1">Gérez vos documents immobiliers en toute sécurité</p>
        </div>
        <a href="{{ route('owner.documents.create') }}" class="inline-flex items-center gap-2 px-5 py-2.5 bg-gray-900 text-white font-semibold rounded-xl hover:bg-gray-800 transition-all text-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
            Ajouter un document
        </a>
    </div>

    {{-- Alerts --}}
    @if($expiring->count())
    <div class="bg-amber-50 border border-amber-200 rounded-2xl p-4">
        <div class="flex items-center gap-2 mb-2">
            <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" /></svg>
            <p class="text-sm font-semibold text-amber-800">{{ $expiring->count() }} document(s) expirent bientôt</p>
        </div>
        <div class="space-y-1">
            @foreach($expiring as $doc)
            <p class="text-xs text-amber-700">• {{ $doc->name }} — expire le {{ $doc->expiry_date->format('d/m/Y') }}</p>
            @endforeach
        </div>
    </div>
    @endif

    @if($expired->count())
    <div class="bg-red-50 border border-red-200 rounded-2xl p-4">
        <div class="flex items-center gap-2 mb-2">
            <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" /></svg>
            <p class="text-sm font-semibold text-red-800">{{ $expired->count() }} document(s) expirés</p>
        </div>
        <div class="space-y-1">
            @foreach($expired as $doc)
            <p class="text-xs text-red-700">• {{ $doc->name }} — expiré le {{ $doc->expiry_date->format('d/m/Y') }}</p>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Filter --}}
    <div class="bg-white rounded-2xl border border-gray-100 p-4">
        <form method="GET" class="flex flex-wrap gap-3 items-end">
            <div>
                <label class="block text-xs font-semibold text-gray-500 mb-1">Catégorie</label>
                <select name="category" class="rounded-xl border-gray-200 text-sm py-2 px-3">
                    <option value="">Toutes</option>
                    @foreach(\App\Models\OwnerDocument::CATEGORIES as $key => $label)
                        <option value="{{ $key }}" {{ request('category') === $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-sm font-medium rounded-xl transition-colors">Filtrer</button>
        </form>
    </div>

    {{-- Grid --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        @forelse($documents as $doc)
        <div class="bg-white rounded-2xl border border-gray-100 p-5 flex flex-col">
            <div class="flex items-start justify-between mb-3">
                <div class="w-10 h-10 rounded-xl bg-blue-50 flex items-center justify-center">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" /></svg>
                </div>
                @if($doc->isExpired())
                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold uppercase bg-red-100 text-red-700">Expiré</span>
                @elseif($doc->isExpiringSoon())
                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold uppercase bg-amber-100 text-amber-700">Expire bientôt</span>
                @else
                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold uppercase bg-green-100 text-green-700">Valide</span>
                @endif
            </div>

            <h3 class="font-semibold text-gray-900 text-sm truncate">{{ $doc->name }}</h3>
            <p class="text-xs text-gray-500 mt-0.5">{{ $doc->category_label }} · {{ $doc->file_size_formatted }}</p>
            @if($doc->expiry_date)
            <p class="text-xs text-gray-400 mt-0.5">Expire : {{ $doc->expiry_date->format('d/m/Y') }}</p>
            @endif
            @if($doc->residence)
            <p class="text-xs text-gray-400 mt-0.5">{{ $doc->residence->name }}</p>
            @endif

            <div class="mt-auto pt-4 flex items-center gap-2">
                <a href="{{ route('owner.documents.download', $doc) }}" class="flex-1 text-center py-2 bg-gray-100 hover:bg-gray-200 text-sm font-medium rounded-xl transition-colors">
                    Télécharger
                </a>
                <form method="POST" action="{{ route('owner.documents.destroy', $doc) }}" onsubmit="return confirm('Supprimer ce document ?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="p-2 text-red-500 hover:bg-red-50 rounded-xl transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" /></svg>
                    </button>
                </form>
            </div>
        </div>
        @empty
        <div class="col-span-full bg-white rounded-2xl border border-gray-100 p-12 text-center">
            <svg class="w-12 h-12 mx-auto text-gray-300 mb-3" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" /></svg>
            <p class="text-gray-400 font-medium">Aucun document</p>
        </div>
        @endforelse
    </div>

    @if($documents->hasPages())
    <div>{{ $documents->withQueryString()->links() }}</div>
    @endif
</div>
@endsection
