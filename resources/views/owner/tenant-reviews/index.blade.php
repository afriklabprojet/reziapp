@extends('layouts.owner')

@section('title', 'Avis locataires — ReziApp')

@section('owner-content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Avis sur les locataires</h1>
            <p class="text-sm text-gray-500 mt-1">Évaluez vos locataires pour mieux gérer vos biens</p>
        </div>
        <a href="{{ route('owner.tenant-reviews.create') }}" class="inline-flex items-center gap-2 px-5 py-2.5 bg-gray-900 text-white font-semibold rounded-xl hover:bg-gray-800 transition-all text-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
            Nouvel avis
        </a>
    </div>

    {{-- Filter --}}
    <div class="bg-white rounded-2xl border border-gray-100 p-4">
        <form method="GET" class="flex flex-wrap gap-3 items-end">
            <div>
                <label class="block text-xs font-semibold text-gray-500 mb-1">Résidence</label>
                <select name="residence_id" class="rounded-xl border-gray-200 text-sm py-2 px-3">
                    <option value="">Toutes</option>
                    @foreach($residences as $r) <option value="{{ $r->id }}" {{ request('residence_id') == $r->id ? 'selected' : '' }}>{{ $r->name }}</option> @endforeach
                </select>
            </div>
            <button type="submit" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-sm font-medium rounded-xl transition-colors">Filtrer</button>
        </form>
    </div>

    {{-- Reviews --}}
    <div class="space-y-4">
        @forelse($reviews as $review)
        <a href="{{ route('owner.tenant-reviews.show', $review) }}" class="block bg-white rounded-2xl border border-gray-100 p-5 hover:shadow-sm transition-shadow">
            <div class="flex items-start justify-between gap-4">
                <div class="flex items-start gap-4">
                    <div class="w-10 h-10 rounded-full bg-gray-200 flex items-center justify-center shrink-0">
                        <span class="text-sm font-bold text-gray-600">{{ strtoupper(substr($review->tenant?->name ?? '?', 0, 1)) }}</span>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-900">{{ $review->tenant?->name ?? 'Locataire inconnu' }}</h3>
                        <p class="text-sm text-gray-500">{{ $review->residence?->name ?? '—' }} · {{ $review->created_at->format('d/m/Y') }}</p>

                        {{-- Star rating --}}
                        <div class="flex items-center gap-1 mt-1">
                            @for($i = 1; $i <= 5; $i++)
                                @if($i <= round($review->average_rating))
                                    <svg class="w-4 h-4 text-amber-400" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" /></svg>
                                @else
                                    <svg class="w-4 h-4 text-gray-200" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" /></svg>
                                @endif
                            @endfor
                            <span class="ml-1 text-sm font-semibold text-gray-700">{{ number_format($review->average_rating, 1) }}</span>
                        </div>

                        @if($review->comment)
                        <p class="text-sm text-gray-600 mt-2 line-clamp-2">{{ $review->comment }}</p>
                        @endif
                    </div>
                </div>
                <div class="text-right shrink-0">
                    @if($review->would_rent_again)
                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold uppercase bg-green-100 text-green-700">Recommandé</span>
                    @else
                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold uppercase bg-red-100 text-red-700">Non recommandé</span>
                    @endif
                </div>
            </div>
        </a>
        @empty
        <div class="bg-white rounded-2xl border border-gray-100 p-12 text-center">
            <svg class="w-12 h-12 mx-auto text-gray-300 mb-3" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 0 1 1.04 0l2.125 5.111a.563.563 0 0 0 .475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 0 0-.182.557l1.285 5.385a.562.562 0 0 1-.84.61l-4.725-2.885a.562.562 0 0 0-.586 0L6.982 20.54a.562.562 0 0 1-.84-.61l1.285-5.386a.562.562 0 0 0-.182-.557l-4.204-3.602a.562.562 0 0 1 .321-.988l5.518-.442a.563.563 0 0 0 .475-.345L11.48 3.5Z" /></svg>
            <p class="text-gray-400 font-medium">Aucun avis sur des locataires</p>
        </div>
        @endforelse
    </div>

    @if($reviews->hasPages())
    <div>{{ $reviews->withQueryString()->links() }}</div>
    @endif
</div>
@endsection
