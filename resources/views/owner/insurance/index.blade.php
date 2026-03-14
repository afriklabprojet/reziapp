@extends('layouts.owner')

@section('title', 'Assurance — REZI')

@section('owner-content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Assurances</h1>
            <p class="text-sm text-gray-500 mt-1">Gérez les assurances de vos résidences</p>
        </div>
        <a href="{{ route('owner.insurance.create') }}" class="inline-flex items-center gap-2 px-5 py-2.5 bg-gray-900 text-white font-semibold rounded-xl hover:bg-gray-800 transition-all text-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
            Nouvelle assurance
        </a>
    </div>

    {{-- Summary --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white rounded-2xl border border-gray-100 p-4">
            <p class="text-xs font-semibold text-gray-400 uppercase">Contrats actifs</p>
            <p class="text-2xl font-bold text-gray-900 mt-1">{{ $subscriptions->where('status', 'active')->count() }}</p>
        </div>
        <div class="bg-white rounded-2xl border border-gray-100 p-4">
            <p class="text-xs font-semibold text-gray-400 uppercase">Coût mensuel total</p>
            <p class="text-2xl font-bold text-gray-900 mt-1">{{ number_format($totalMonthlyCost, 0, ',', ' ') }} <span class="text-sm font-normal text-gray-500">FCFA</span></p>
        </div>
        <div class="bg-white rounded-2xl border border-gray-100 p-4">
            <p class="text-xs font-semibold text-gray-400 uppercase">Expirent bientôt</p>
            <p class="text-2xl font-bold {{ $expiringSoon->count() > 0 ? 'text-amber-600' : 'text-gray-900' }} mt-1">{{ $expiringSoon->count() }}</p>
        </div>
    </div>

    {{-- Expiring Alert --}}
    @if($expiringSoon->count())
    <div class="bg-amber-50 border border-amber-200 rounded-2xl p-4">
        <p class="text-sm font-semibold text-amber-800">⚠️ {{ $expiringSoon->count() }} contrat(s) expirent dans les 30 prochains jours</p>
        @foreach($expiringSoon as $sub)
        <p class="text-xs text-amber-700 mt-1">• {{ $sub->provider }} — {{ $sub->residence?->name }} — expire le {{ $sub->end_date->format('d/m/Y') }}</p>
        @endforeach
    </div>
    @endif

    {{-- Subscriptions List --}}
    <div class="space-y-3">
        @forelse($subscriptions as $sub)
        <div class="bg-white rounded-2xl border border-gray-100 p-5">
            <div class="flex items-start justify-between gap-4">
                <div class="flex items-start gap-4">
                    <div class="w-10 h-10 rounded-xl bg-{{ $sub->status_color }}-100 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-{{ $sub->status_color }}-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" /></svg>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="font-semibold text-gray-900">{{ $sub->provider }}</h3>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold uppercase bg-{{ $sub->status_color }}-100 text-{{ $sub->status_color }}-700">
                                {{ $sub->status === 'active' ? 'Actif' : ($sub->status === 'expired' ? 'Expiré' : 'Annulé') }}
                            </span>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold uppercase bg-blue-50 text-blue-700">
                                {{ $sub->coverage_type_label ?? ucfirst($sub->coverage_type) }}
                            </span>
                        </div>
                        <p class="text-sm text-gray-500 mt-0.5">{{ $sub->residence?->name ?? 'Aucune résidence' }}</p>
                        <p class="text-sm text-gray-500">N° police : {{ $sub->policy_number ?? '—' }}</p>
                        <p class="text-xs text-gray-400 mt-1">
                            {{ $sub->start_date->format('d/m/Y') }} → {{ $sub->end_date->format('d/m/Y') }}
                        </p>
                    </div>
                </div>
                <div class="text-right shrink-0">
                    <p class="text-lg font-bold text-gray-900">{{ number_format($sub->monthly_premium, 0, ',', ' ') }}</p>
                    <p class="text-xs text-gray-400">FCFA/mois</p>
                    @if($sub->status === 'active')
                    <form method="POST" action="{{ route('owner.insurance.cancel', $sub) }}" class="mt-2" onsubmit="return confirm('Annuler ce contrat ?')">
                        @csrf @method('PATCH')
                        <button type="submit" class="text-xs text-red-500 hover:text-red-700 font-medium">Annuler</button>
                    </form>
                    @endif
                </div>
            </div>
        </div>
        @empty
        <div class="bg-white rounded-2xl border border-gray-100 p-12 text-center">
            <svg class="w-12 h-12 mx-auto text-gray-300 mb-3" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" /></svg>
            <p class="text-gray-400 font-medium">Aucun contrat d'assurance</p>
        </div>
        @endforelse
    </div>

    @if($subscriptions->hasPages())
    <div>{{ $subscriptions->withQueryString()->links() }}</div>
    @endif
</div>
@endsection
