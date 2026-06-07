@extends('layouts.owner')

@section('title', 'Réclamations assurance — Rezi App')

@section('owner-content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Réclamations assurance</h1>
            <p class="text-sm text-gray-500 mt-1">Déclarez et suivez vos sinistres</p>
        </div>
        <a href="{{ route('owner.insurance-claims.create') }}" class="inline-flex items-center gap-2 px-4 py-2.5 bg-gray-900 text-white font-semibold rounded-xl hover:bg-gray-800 text-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
            Nouvelle réclamation
        </a>
    </div>

    {{-- Claims List --}}
    <div class="space-y-3">
        @forelse($claims as $claim)
        <a href="{{ route('owner.insurance-claims.show', $claim) }}" class="block bg-white rounded-2xl border border-gray-100 p-5 hover:shadow-sm transition-shadow">
            <div class="flex items-center justify-between gap-4">
                <div class="flex items-center gap-4 min-w-0">
                    <div class="w-10 h-10 rounded-xl {{ $claim->status === 'approved' || $claim->status === 'paid' ? 'bg-green-100' : ($claim->status === 'rejected' ? 'bg-red-100' : ($claim->status === 'under_review' ? 'bg-blue-100' : 'bg-gray-100')) }} flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 {{ $claim->status === 'approved' || $claim->status === 'paid' ? 'text-green-600' : ($claim->status === 'rejected' ? 'text-red-600' : ($claim->status === 'under_review' ? 'text-blue-600' : 'text-gray-400')) }}" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" /></svg>
                    </div>
                    <div class="min-w-0">
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-mono text-gray-500">#{{ $claim->claim_number }}</span>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold uppercase
                                {{ $claim->status === 'approved' || $claim->status === 'paid' ? 'bg-green-100 text-green-700' : ($claim->status === 'rejected' ? 'bg-red-100 text-red-700' : ($claim->status === 'under_review' ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-500')) }}">
                                {{ \App\Models\InsuranceClaim::getStatusLabels()[$claim->status] ?? $claim->status }}
                            </span>
                        </div>
                        <p class="font-semibold text-gray-900 mt-1 truncate">{{ \App\Models\InsuranceClaim::getClaimTypeLabels()[$claim->claim_type] ?? $claim->claim_type }}</p>
                        <p class="text-sm text-gray-500">{{ $claim->bookingInsurance?->booking?->residence?->name ?? '-' }}</p>
                    </div>
                </div>
                <div class="text-right shrink-0">
                    <p class="text-lg font-bold text-gray-900">{{ number_format($claim->claimed_amount, 0, ',', ' ') }} F</p>
                    <p class="text-xs text-gray-400">{{ $claim->created_at->format('d/m/Y') }}</p>
                </div>
            </div>
        </a>
        @empty
        <div class="bg-white rounded-2xl border border-gray-100 p-12 text-center">
            <svg class="w-12 h-12 mx-auto text-gray-300 mb-3" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" /></svg>
            <p class="text-gray-400 font-medium">Aucune réclamation</p>
        </div>
        @endforelse
    </div>

    @if($claims->hasPages())
    <div>{{ $claims->withQueryString()->links() }}</div>
    @endif
</div>
@endsection
