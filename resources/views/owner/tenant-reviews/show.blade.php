@extends('layouts.owner')

@section('title', 'Avis locataire — Rezi App')

@section('owner-content')
<div class="max-w-2xl mx-auto space-y-6">
    <div>
        <a href="{{ route('owner.tenant-reviews.index') }}" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" /></svg>
            Retour
        </a>
    </div>

    <div class="bg-white rounded-2xl border border-gray-100 p-6">
        {{-- Tenant Info --}}
        <div class="flex items-center gap-4 mb-6">
            <div class="w-14 h-14 rounded-full bg-gray-200 flex items-center justify-center">
                <span class="text-xl font-bold text-gray-600">{{ strtoupper(substr($review->tenant?->name ?? '?', 0, 1)) }}</span>
            </div>
            <div>
                <h1 class="text-xl font-bold text-gray-900">{{ $review->tenant?->name ?? 'Locataire inconnu' }}</h1>
                <p class="text-sm text-gray-500">{{ $review->residence?->name ?? '—' }} · {{ $review->created_at->format('d/m/Y') }}</p>
            </div>
            <div class="ml-auto">
                @if($review->would_rent_again)
                    <span class="inline-flex items-center px-3 py-1.5 rounded-xl text-xs font-bold uppercase bg-green-100 text-green-700">Recommandé</span>
                @else
                    <span class="inline-flex items-center px-3 py-1.5 rounded-xl text-xs font-bold uppercase bg-red-100 text-red-700">Non recommandé</span>
                @endif
            </div>
        </div>

        {{-- Overall Rating --}}
        <div class="text-center p-6 bg-gray-50 rounded-xl mb-6">
            <div class="flex items-center justify-center gap-1 mb-1">
                @for($i = 1; $i <= 5; $i++)
                    @if($i <= round($review->average_rating))
                        <svg class="w-6 h-6 text-amber-400" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" /></svg>
                    @else
                        <svg class="w-6 h-6 text-gray-200" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" /></svg>
                    @endif
                @endfor
            </div>
            <p class="text-2xl font-bold text-gray-900">{{ number_format($review->average_rating, 1) }}/5</p>
            <p class="text-xs text-gray-500 mt-0.5">Note moyenne</p>
        </div>

        {{-- Dimension Ratings --}}
        <div class="space-y-3 mb-6">
            @foreach(\App\Models\TenantReview::DIMENSIONS as $key => $label)
            <div class="flex items-center justify-between">
                <span class="text-sm text-gray-700">{{ $label }}</span>
                <div class="flex items-center gap-2">
                    <div class="w-32 bg-gray-100 rounded-full h-2 overflow-hidden">
                        <div class="bg-amber-400 h-full rounded-full" style="width: {{ ($review->$key / 5) * 100 }}%"></div>
                    </div>
                    <span class="text-sm font-semibold text-gray-700 w-8 text-right">{{ $review->$key }}/5</span>
                </div>
            </div>
            @endforeach
        </div>

        {{-- Comment --}}
        @if($review->comment)
        <div class="p-4 bg-gray-50 rounded-xl">
            <p class="text-sm text-gray-700 whitespace-pre-line">{{ $review->comment }}</p>
        </div>
        @endif
    </div>

    {{-- Tenant Score --}}
    @if($tenantScore)
    <div class="bg-white rounded-2xl border border-gray-100 p-6">
        <h2 class="font-semibold text-gray-900 mb-4">Score global du locataire</h2>
        <div class="grid grid-cols-2 gap-4">
            <div class="text-center p-4 bg-blue-50 rounded-xl">
                <p class="text-2xl font-bold text-blue-700">{{ number_format($tenantScore['average_rating'], 1) }}</p>
                <p class="text-xs text-blue-600 mt-0.5">Note moyenne</p>
            </div>
            <div class="text-center p-4 bg-green-50 rounded-xl">
                <p class="text-2xl font-bold text-green-700">{{ $tenantScore['would_rent_again_percentage'] }}%</p>
                <p class="text-xs text-green-600 mt-0.5">Reloueraient</p>
            </div>
        </div>
    </div>
    @endif

    {{-- Actions --}}
    <div class="flex gap-3">
        <form method="POST" action="{{ route('owner.tenant-reviews.destroy', $review) }}" onsubmit="return confirm('Supprimer cet avis ?')">
            @csrf @method('DELETE')
            <button type="submit" class="px-5 py-2.5 bg-red-50 text-red-600 font-semibold rounded-xl hover:bg-red-100 transition-colors text-sm">
                Supprimer l'avis
            </button>
        </form>
    </div>
</div>
@endsection
