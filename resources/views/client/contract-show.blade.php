@extends('layouts.client', ['sidebarActive' => 'contracts'])

@section('title', 'Contrat ' . $contract->reference . ' - ReziApp')

@section('client-content')
    <div class="space-y-6">

        {{-- Fil d'Ariane --}}
        <nav class="text-sm text-gray-400 flex items-center gap-2">
            <a href="{{ route('client.contracts') }}" class="hover:text-[#CC5A00]">Mes contrats</a>
            <span>›</span>
            <span class="text-gray-700 font-mono">{{ $contract->reference }}</span>
        </nav>

        {{-- En-tête --}}
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">{{ $contract->reference }}</h1>
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
                <span class="inline-flex mt-2 px-3 py-1 rounded-full text-sm font-semibold {{ $statusConfig['bg'] }} {{ $statusConfig['text'] }}">
                    {{ $statusConfig['label'] }}
                </span>
            </div>
            <div class="flex items-center gap-2">
                @if ($contract->pdf_path)
                    <a href="{{ route('client.contracts.download', $contract) }}"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-red-600 text-white rounded-xl text-sm font-semibold hover:bg-red-700 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                        </svg>
                        Télécharger PDF
                    </a>
                @endif
            </div>
        </div>

        {{-- Alertes --}}
        @if (session('success'))
            <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl text-sm">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm">
                {{ session('error') }}
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            {{-- Colonne principale --}}
            <div class="lg:col-span-2 space-y-5">

                {{-- Parties --}}
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <h2 class="font-semibold text-gray-800 mb-4">Parties du contrat</h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="bg-gray-50 rounded-xl p-4">
                            <div class="text-xs text-gray-400 uppercase tracking-wide mb-1">Bailleur (Propriétaire)</div>
                            <div class="font-semibold text-gray-900">{{ $contract->owner->name }}</div>
                            <div class="text-sm text-gray-500">{{ $contract->owner->email }}</div>
                            @if ($contract->owner->phone)
                                <div class="text-sm text-gray-500">{{ $contract->owner->phone }}</div>
                            @endif
                        </div>
                        <div class="bg-[#FFF4EB] rounded-xl p-4 border border-[#FFE7D1]">
                            <div class="text-xs text-[#F16A00] uppercase tracking-wide mb-1">Locataire (Vous)</div>
                            <div class="font-semibold text-gray-900">{{ $contract->tenant->name }}</div>
                            <div class="text-sm text-gray-500">{{ $contract->tenant->email }}</div>
                            @if ($contract->tenant->phone)
                                <div class="text-sm text-gray-500">{{ $contract->tenant->phone }}</div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Bien loué --}}
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <h2 class="font-semibold text-gray-800 mb-4">Bien loué</h2>
                    <div class="flex items-start gap-4">
                        @if ($contract->residence->photos->count())
                            <img src="{{ asset('storage/' . $contract->residence->photos->first()->path) }}" alt=""
                                class="w-20 h-20 rounded-xl object-cover shrink-0">
                        @endif
                        <div>
                            <div class="font-semibold text-gray-900">{{ $contract->residence->name }}</div>
                            <div class="text-sm text-gray-500">
                                {{ $contract->residence->address }}, {{ $contract->residence->commune }}
                            </div>
                            @if ($contract->residence->surface_area)
                                <div class="text-xs text-gray-400 mt-1">
                                    {{ $contract->residence->surface_area }} m²
                                    @if ($contract->residence->bedrooms)
                                        · {{ $contract->residence->bedrooms }} chambre(s)
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Conditions financières --}}
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <h2 class="font-semibold text-gray-800 mb-4">Conditions financières</h2>
                    <div class="space-y-3">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Montant mensuel de location</span>
                            <span class="font-semibold text-gray-900">
                                {{ number_format($contract->monthly_rent, 0, ',', ' ') }} FCFA
                            </span>
                        </div>
                        @if ($contract->charges_amount)
                            <div class="flex justify-between">
                                <span class="text-gray-600">Charges mensuelles</span>
                                <span class="font-semibold">
                                    {{ number_format($contract->charges_amount, 0, ',', ' ') }} FCFA
                                </span>
                            </div>
                            <div class="flex justify-between border-t pt-3 mt-3">
                                <span class="text-gray-900 font-medium">Total mensuel</span>
                                <span class="font-bold text-[#CC5A00]">
                                    {{ number_format($contract->monthly_rent + $contract->charges_amount, 0, ',', ' ') }}
                                    FCFA
                                </span>
                            </div>
                        @endif
                        @if ($contract->deposit_amount)
                            <div class="flex justify-between">
                                <span class="text-gray-600">Dépôt de garantie</span>
                                <span class="font-semibold">
                                    {{ number_format($contract->deposit_amount, 0, ',', ' ') }} FCFA
                                </span>
                            </div>
                        @endif
                        @if ($contract->payment_day)
                            <div class="flex justify-between">
                                <span class="text-gray-600">Jour d'échéance</span>
                                <span class="font-semibold">Le {{ $contract->payment_day }} du mois</span>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Clauses spéciales --}}
                @if ($contract->special_clauses)
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                        <h2 class="font-semibold text-gray-800 mb-2">Clauses particulières</h2>
                        <p class="text-sm text-gray-600 leading-relaxed whitespace-pre-line">{{ $contract->special_clauses }}</p>
                    </div>
                @endif

                {{-- Services inclus --}}
                @if($contract->included_services && count($contract->included_services))
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <h2 class="font-semibold text-gray-800 mb-3">Services inclus</h2>
                    <div class="flex flex-wrap gap-2">
                        @foreach($contract->included_services as $service)
                            <span class="inline-flex items-center px-3 py-1 bg-[#FFF4EB] text-[#A34700] rounded-full text-sm font-medium">
                                ✓ {{ $service }}
                            </span>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>

            {{-- Colonne droite --}}
            <div class="space-y-4">

                {{-- Détails du bail --}}
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 space-y-3">
                    <h3 class="font-semibold text-gray-800">Détails du bail</h3>
                    <div class="text-sm space-y-2">
                        <div class="flex justify-between">
                            <span class="text-gray-500">Type</span>
                            <span class="font-medium">
                                @switch($contract->lease_type)
                                    @case('short_term') Court terme @break
                                    @case('monthly') Mensuel @break
                                    @case('fixed_term') Durée déterminée @break
                                    @default {{ $contract->lease_type }}
                                @endswitch
                            </span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500">Début</span>
                            <span class="font-medium">{{ $contract->start_date->format('d/m/Y') }}</span>
                        </div>
                        @if ($contract->end_date)
                            <div class="flex justify-between">
                                <span class="text-gray-500">Fin</span>
                                <span class="font-medium">{{ $contract->end_date->format('d/m/Y') }}</span>
                            </div>
                            @if ($contract->duration_in_months)
                                <div class="flex justify-between">
                                    <span class="text-gray-500">Durée</span>
                                    <span class="font-medium">{{ $contract->duration_in_months }} mois</span>
                                </div>
                            @endif
                        @endif
                    </div>
                </div>

                {{-- Signatures --}}
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 space-y-3">
                    <h3 class="font-semibold text-gray-800">Signatures</h3>

                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center
                            {{ $contract->owner_signed_at ? 'bg-green-100' : 'bg-gray-100' }}">
                            @if ($contract->owner_signed_at)
                                <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M5 13l4 4L19 7" />
                                </svg>
                            @else
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            @endif
                        </div>
                        <div>
                            <div class="text-sm font-medium text-gray-800">Propriétaire</div>
                            <div class="text-xs text-gray-400">
                                {{ $contract->owner_signed_at ? 'Signé le ' . $contract->owner_signed_at->format('d/m/Y à H:i') : 'En attente' }}
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center
                            {{ $contract->tenant_signed_at ? 'bg-green-100' : 'bg-amber-100' }}">
                            @if ($contract->tenant_signed_at)
                                <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M5 13l4 4L19 7" />
                                </svg>
                            @else
                                <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                </svg>
                            @endif
                        </div>
                        <div>
                            <div class="text-sm font-medium text-gray-800">Vous (Locataire)</div>
                            <div class="text-xs text-gray-400">
                                {{ $contract->tenant_signed_at ? 'Signé le ' . $contract->tenant_signed_at->format('d/m/Y à H:i') : 'En attente de votre signature' }}
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Action de signature --}}
                @if ($contract->status === 'pending_tenant' && !$contract->tenant_signed_at)
                    <div class="bg-amber-50 rounded-2xl border border-amber-200 p-5">
                        <div class="flex items-start gap-3 mb-4">
                            <svg class="w-6 h-6 text-amber-500 shrink-0 mt-0.5" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                            </svg>
                            <div>
                                <h3 class="font-semibold text-amber-800">Signature requise</h3>
                                <p class="text-sm text-amber-700 mt-1">
                                    Le propriétaire {{ $contract->owner->name }} vous demande de signer ce contrat de bail.
                                    En signant, vous acceptez les termes et conditions ci-dessus.
                                </p>
                            </div>
                        </div>
                        <form method="POST" action="{{ route('client.contracts.sign', $contract) }}">
                            @csrf
                            <button type="submit"
                                class="w-full py-3 bg-[#F16A00] text-white rounded-xl text-sm font-bold hover:bg-[#CC5A00] transition flex items-center justify-center gap-2"
                                onclick="return confirm('En signant ce contrat, vous acceptez toutes les conditions mentionnées. Confirmer la signature ?')">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                </svg>
                                Signer le contrat
                            </button>
                        </form>
                    </div>
                @elseif ($contract->status === 'active')
                    <div class="bg-green-50 rounded-2xl border border-green-200 p-5">
                        <div class="flex items-center gap-3">
                            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <div>
                                <h3 class="font-semibold text-green-800">Contrat actif</h3>
                                <p class="text-sm text-green-700">
                                    Ce contrat est signé par les deux parties et actuellement en vigueur.
                                </p>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
