@extends('layouts.client', ['sidebarActive' => 'contracts'])

@section('title', 'Mes contrats - Rezi App')

@section('client-content')
    {{-- En-tête --}}
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-gray-900">Mes contrats</h1>
        <p class="text-gray-600">Gérez vos baux et contrats de location</p>
    </div>

    {{-- Statistiques --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25z" />
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-900">{{ $contractStats['total'] }}</p>
                    <p class="text-xs text-gray-500">Total</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-green-600">{{ $contractStats['active'] }}</p>
                    <p class="text-xs text-gray-500">Actifs</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-amber-100 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-amber-600">{{ $contractStats['pending'] }}</p>
                    <p class="text-xs text-gray-500">En attente</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-gray-100 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-500">{{ $contractStats['terminated'] }}</p>
                    <p class="text-xs text-gray-500">Terminés</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Liste des contrats --}}
    @if ($contracts->isEmpty())
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-12 text-center">
            <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                        d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25z" />
                </svg>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 mb-1">Aucun contrat</h3>
            <p class="text-gray-500 text-sm max-w-md mx-auto">
                Vos contrats de bail apparaîtront ici lorsqu'un propriétaire vous en enverra un après une réservation.
            </p>
            <a href="{{ route('residences.index') }}"
                class="inline-flex items-center mt-6 px-5 py-2.5 bg-[#F16A00] hover:bg-[#CC5A00] text-white font-medium rounded-lg transition">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                Explorer les résidences
            </a>
        </div>
    @else
        <div class="space-y-4">
            @foreach ($contracts as $contract)
                <a href="{{ route('client.contracts.show', $contract) }}"
                    class="block bg-white rounded-xl shadow-sm border border-gray-100 hover:shadow-md transition overflow-hidden">
                    <div class="flex flex-col sm:flex-row">
                        {{-- Photo résidence --}}
                        @if ($contract->residence && $contract->residence->photos->isNotEmpty())
                            <div class="sm:w-40 h-32 sm:h-auto shrink-0">
                                <img loading="lazy" src="{{ storage_url($contract->residence->photos->first()?->path) }}"
                                    alt="{{ $contract->residence->title }}" class="w-full h-full object-cover">
                            </div>
                        @endif

                        {{-- Infos contrat --}}
                        <div class="flex-1 p-5">
                            <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center gap-2 mb-1">
                                        <h3 class="font-semibold text-gray-900 truncate">
                                            {{ $contract->residence?->title ?? 'Résidence supprimée' }}
                                        </h3>
                                        {{-- Badge statut --}}
                                        @php
                                            $statusConfig = match ($contract->status) {
                                                'active' => ['bg' => 'bg-green-100', 'text' => 'text-green-700', 'label' => 'Actif'],
                                                'pending_tenant' => ['bg' => 'bg-amber-100', 'text' => 'text-amber-700', 'label' => 'À signer'],
                                                'pending_owner' => ['bg' => 'bg-blue-100', 'text' => 'text-blue-700', 'label' => 'En attente propriétaire'],
                                                'draft' => ['bg' => 'bg-gray-100', 'text' => 'text-gray-600', 'label' => 'Brouillon'],
                                                'terminated' => ['bg' => 'bg-red-100', 'text' => 'text-red-700', 'label' => 'Résilié'],
                                                'expired' => ['bg' => 'bg-gray-100', 'text' => 'text-gray-500', 'label' => 'Expiré'],
                                                default => ['bg' => 'bg-gray-100', 'text' => 'text-gray-600', 'label' => ucfirst($contract->status)],
                                            };
                                        @endphp
                                        <span
                                            class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold {{ $statusConfig['bg'] }} {{ $statusConfig['text'] }}">
                                            {{ $statusConfig['label'] }}
                                        </span>
                                    </div>

                                    <p class="text-sm text-gray-500 mb-2">
                                        Réf. {{ $contract->reference }} —
                                        Propriétaire : {{ $contract->owner?->name ?? 'N/A' }}
                                    </p>

                                    <div class="flex flex-wrap items-center gap-x-5 gap-y-1 text-sm text-gray-600">
                                        <span class="flex items-center gap-1">
                                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                            {{ $contract->start_date->format('d/m/Y') }}
                                            @if ($contract->end_date)
                                                — {{ $contract->end_date->format('d/m/Y') }}
                                            @endif
                                        </span>
                                        <span class="flex items-center gap-1 font-semibold text-gray-900">
                                            {{ number_format($contract->monthly_rent, 0, ',', ' ') }} FCFA/mois
                                        </span>
                                        @if ($contract->deposit_amount)
                                            <span class="text-gray-400">
                                                Caution :
                                                {{ number_format($contract->deposit_amount, 0, ',', ' ') }} FCFA
                                            </span>
                                        @endif
                                    </div>
                                </div>

                                {{-- Actions --}}
                                <div class="flex items-center gap-2 shrink-0 flex-wrap">
                                    @if ($contract->status === 'pending_tenant')
                                        <form action="{{ route('client.contracts.sign', $contract) }}" method="POST">
                                            @csrf
                                            <button type="submit"
                                                class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg transition"
                                                onclick="return confirm('Voulez-vous signer ce contrat ?')">
                                                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z" />
                                                </svg>
                                                Signer
                                            </button>
                                        </form>
                                    @endif

                                    @if ($contract->status === 'active')
                                        @if ($contract->termination_requested_at)
                                            <span class="inline-flex items-center gap-1.5 px-3 py-2 bg-amber-50 text-amber-700 text-sm font-medium rounded-lg border border-amber-200">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                                Résiliation en attente
                                            </span>
                                            <form action="{{ route('client.contracts.cancel-termination', $contract) }}" method="POST"
                                                  onsubmit="return confirm('Annuler votre demande de résiliation ?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                    class="inline-flex items-center px-3 py-2 text-xs font-medium text-amber-700 bg-amber-50 hover:bg-amber-100 border border-amber-200 rounded-lg transition">
                                                    Annuler la demande
                                                </button>
                                            </form>
                                        @else
                                            <button type="button"
                                                x-data
                                                @click="$dispatch('open-termination-modal', { contractId: {{ $contract->id }}, contractRef: '{{ $contract->reference }}', actionUrl: '{{ route('client.contracts.request-termination', $contract) }}' })"
                                                class="inline-flex items-center px-3 py-2 text-sm font-medium text-red-600 bg-red-50 hover:bg-red-100 border border-red-200 rounded-lg transition">
                                                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                                Demander résiliation
                                            </button>
                                        @endif
                                    @endif

                                    @if ($contract->pdf_path)
                                        <a href="{{ route('client.contracts.download', $contract) }}"
                                            class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-700 bg-gray-50 hover:bg-gray-100 rounded-lg transition"
                                            target="_blank">
                                            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                                            </svg>
                                            PDF
                                        </a>
                                    @endif
                                </div>
                            </div>

                            {{-- Signatures --}}
                            <div class="mt-3 pt-3 border-t border-gray-100 flex flex-wrap gap-4 text-xs text-gray-500">
                                <span class="flex items-center gap-1">
                                    @if ($contract->owner_signed_at)
                                        <svg class="w-3.5 h-3.5 text-green-500" fill="currentColor"
                                            viewBox="0 0 20 20">
                                            <path fill-rule="evenodd"
                                                d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                                clip-rule="evenodd" />
                                        </svg>
                                        Propriétaire signé le
                                        {{ $contract->owner_signed_at->format('d/m/Y') }}
                                    @else
                                        <svg class="w-3.5 h-3.5 text-gray-300" fill="currentColor"
                                            viewBox="0 0 20 20">
                                            <path fill-rule="evenodd"
                                                d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z"
                                                clip-rule="evenodd" />
                                        </svg>
                                        Propriétaire non signé
                                    @endif
                                </span>
                                <span class="flex items-center gap-1">
                                    @if ($contract->tenant_signed_at)
                                        <svg class="w-3.5 h-3.5 text-green-500" fill="currentColor"
                                            viewBox="0 0 20 20">
                                            <path fill-rule="evenodd"
                                                d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                                clip-rule="evenodd" />
                                        </svg>
                                        Vous avez signé le {{ $contract->tenant_signed_at->format('d/m/Y') }}
                                    @else
                                        <svg class="w-3.5 h-3.5 text-amber-400" fill="currentColor"
                                            viewBox="0 0 20 20">
                                            <path fill-rule="evenodd"
                                                d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z"
                                                clip-rule="evenodd" />
                                        </svg>
                                        En attente de votre signature
                                    @endif
                                </span>
                            </div>
                        </div>
                    </div>
                </a>
            @endforeach
        </div>

        {{-- Pagination --}}
        @if ($contracts->hasPages())
            <div class="mt-8">
                {{ $contracts->links() }}
            </div>
        @endif
    @endif
@endsection

{{-- Modal résiliation --}}
<div
    x-data="{
        open: false,
        contractId: null,
        contractRef: '',
        actionUrl: '',
        reason: '',
        submitting: false,
    }"
    @open-termination-modal.window="
        contractId = $event.detail.contractId;
        contractRef = $event.detail.contractRef;
        actionUrl = $event.detail.actionUrl;
        reason = '';
        open = true;
    "
    x-show="open"
    x-cloak
    class="fixed inset-0 z-50 flex items-center justify-center p-4"
    style="display: none;"
>
    {{-- Overlay --}}
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="open = false"></div>

    {{-- Contenu --}}
    <div class="relative w-full max-w-md bg-white rounded-2xl shadow-xl p-6 z-10" @click.stop>
        <div class="flex items-start gap-3 mb-4">
            <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126z" />
                </svg>
            </div>
            <div>
                <h3 class="text-base font-semibold text-gray-900">Demander la résiliation</h3>
                <p class="text-sm text-gray-500 mt-0.5">Contrat <span class="font-medium" x-text="contractRef"></span></p>
            </div>
        </div>

        <p class="text-sm text-gray-600 mb-4">
            Votre demande sera transmise au propriétaire. Cette action n'est pas une résiliation immédiate — un accord mutuel ou un délai de préavis sera nécessaire.
        </p>

        <form :action="actionUrl" method="POST" @submit="submitting = true">
            @csrf
            <label class="block text-sm font-medium text-gray-700 mb-1">
                Motif de résiliation <span class="text-red-500">*</span>
            </label>
            <textarea
                name="reason"
                x-model="reason"
                rows="4"
                placeholder="Décrivez le motif de votre demande (déménagement, raisons professionnelles, etc.) — minimum 20 caractères."
                class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm placeholder-gray-400 focus:ring-2 focus:ring-red-300 focus:border-red-400 resize-none"
                required
                minlength="20"
                maxlength="1000"
            ></textarea>
            <p class="text-xs text-gray-400 mt-1" x-text="reason.length + '/1000 caractères'"></p>

            @error('reason')
                <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
            @enderror

            <div class="flex gap-3 mt-5">
                <button type="button" @click="open = false"
                    class="flex-1 px-4 py-2.5 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-xl transition">
                    Annuler
                </button>
                <button type="submit"
                    :disabled="submitting || reason.length < 20"
                    class="flex-1 px-4 py-2.5 text-sm font-medium text-white bg-red-600 hover:bg-red-700 disabled:bg-red-300 disabled:cursor-not-allowed rounded-xl transition">
                    <span x-show="!submitting">Envoyer la demande</span>
                    <span x-show="submitting">Envoi en cours…</span>
                </button>
            </div>
        </form>
    </div>
</div>
