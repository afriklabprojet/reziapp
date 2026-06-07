@extends('layouts.owner')

@section('title', 'Contrat #' . $insurance->policy_number . ' — Rezi Studio Meublé Faya')

@section('owner-content')
<div class="max-w-4xl mx-auto space-y-6">

    {{-- Header --}}
    <div class="flex items-center justify-between gap-4 flex-wrap">
        <div class="flex items-center gap-3">
            <a href="{{ route('owner.insurance.index') }}" class="text-gray-400 hover:text-gray-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/></svg>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-gray-900">{{ $insurance->provider }}</h1>
                <p class="text-sm text-gray-500">Police N° {{ $insurance->policy_number }}</p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold uppercase bg-{{ $insurance->status_color }}-100 text-{{ $insurance->status_color }}-700">
                {{ $insurance->status_label }}
            </span>
            @if($insurance->isActive())
            <form method="POST" action="{{ route('owner.insurance.cancel', $insurance) }}" onsubmit="return confirm('Résilier ce contrat ?')">
                @csrf
                <input type="hidden" name="reason" value="Résiliation à la demande du propriétaire">
                <button type="submit" class="px-4 py-1.5 text-sm font-medium text-red-600 border border-red-200 rounded-xl hover:bg-red-50 transition-colors">
                    Résilier
                </button>
            </form>
            @elseif($insurance->canBeRenewed())
            <form method="POST" action="{{ route('owner.insurance.renew', $insurance) }}" onsubmit="return confirm('Renouveler ce contrat pour 1 an ?')">
                @csrf
                <button type="submit" class="px-4 py-1.5 text-sm font-medium bg-blue-600 text-white rounded-xl hover:bg-blue-700 transition-colors">
                    Renouveler
                </button>
            </form>
            @endif
        </div>
    </div>

    @if(session('success'))
    <div class="bg-green-50 border border-green-200 rounded-xl p-3 text-sm text-green-800">{{ session('success') }}</div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Left: Contract Details --}}
        <div class="lg:col-span-2 space-y-5">

            {{-- Main Info --}}
            <div class="bg-white rounded-2xl border border-gray-100 p-5">
                <h2 class="font-bold text-gray-900 mb-4">Détails du contrat</h2>
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <p class="text-xs text-gray-400 uppercase tracking-wide font-semibold">Résidence assurée</p>
                        <p class="font-medium text-gray-800 mt-0.5">{{ $insurance->residence?->name ?? '—' }}</p>
                        @if($insurance->residence?->address)
                        <p class="text-xs text-gray-500">{{ $insurance->residence->address }}</p>
                        @endif
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 uppercase tracking-wide font-semibold">Type de couverture</p>
                        <p class="font-medium text-gray-800 mt-0.5">{{ $insurance->coverage_type_label }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 uppercase tracking-wide font-semibold">Date de début</p>
                        <p class="font-medium text-gray-800 mt-0.5">{{ $insurance->start_date->format('d/m/Y') }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 uppercase tracking-wide font-semibold">Date d'expiration</p>
                        <p class="font-medium {{ $insurance->isExpiringSoon() ? 'text-amber-700' : 'text-gray-800' }} mt-0.5">
                            {{ $insurance->end_date->format('d/m/Y') }}
                            @if($insurance->isActive())
                            <span class="text-xs text-gray-400 font-normal">({{ $insurance->days_remaining }} j.)</span>
                            @endif
                        </p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 uppercase tracking-wide font-semibold">Prime mensuelle</p>
                        <p class="font-bold text-xl text-gray-900 mt-0.5">
                            {{ number_format($insurance->monthly_premium, 0, ',', ' ') }}
                            <span class="text-sm font-normal text-gray-500">FCFA</span>
                        </p>
                        @if($insurance->suggested_premium)
                        <p class="text-xs {{ abs($insurance->premium_variance) > 100 ? ($insurance->premium_variance > 0 ? 'text-red-500' : 'text-green-600') : 'text-gray-400' }}">
                            Estimation actuarielle : {{ number_format($insurance->suggested_premium, 0, ',', ' ') }} FCFA
                            @if(abs($insurance->premium_variance) > 100)
                            ({{ $insurance->premium_variance > 0 ? '+' : '' }}{{ number_format($insurance->premium_variance, 0, ',', ' ') }})
                            @endif
                        </p>
                        @endif
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 uppercase tracking-wide font-semibold">Prime annuelle</p>
                        <p class="font-medium text-gray-800 mt-0.5">{{ number_format($insurance->annual_cost, 0, ',', ' ') }} FCFA</p>
                    </div>
                    @if($insurance->claim_count)
                    <div>
                        <p class="text-xs text-gray-400 uppercase tracking-wide font-semibold">Sinistres déclarés</p>
                        <p class="font-medium text-gray-800 mt-0.5">{{ $insurance->claim_count }}</p>
                    </div>
                    @endif
                    @if($insurance->cancellation_reason && $insurance->status === 'cancelled')
                    <div class="col-span-2">
                        <p class="text-xs text-gray-400 uppercase tracking-wide font-semibold">Motif de résiliation</p>
                        <p class="font-medium text-gray-800 mt-0.5">{{ $insurance->cancellation_reason }}</p>
                    </div>
                    @endif
                </div>
            </div>

            {{-- Risk Score --}}
            @if($insurance->risk_score)
            @php
                $sc     = $insurance->risk_score;
                $grade  = $insurance->risk_grade;
                $label  = $insurance->risk_label;
                $col    = $insurance->risk_color;
                $factors = $insurance->risk_factors ?? [];
                $factorLabels = [
                    'commune_risk'      => 'Zone géographique',
                    'property_type'     => 'Type de bien',
                    'property_age'      => 'Âge du bien',
                    'occupancy_rate'    => "Taux d'occupation",
                    'claim_history'     => 'Historique sinistres',
                    'value_declared'    => 'Valeur déclarée',
                    'security_features' => 'Sécurité',
                    'floor_level'       => 'Niveau / étage',
                ];
            @endphp
            <div class="bg-white rounded-2xl border border-gray-100 p-5">
                <h2 class="font-bold text-gray-900 mb-4">Score de risque</h2>
                <div class="flex items-center gap-5">
                    <div class="w-20 h-20 rounded-full border-4 border-{{ $col }}-400 flex flex-col items-center justify-center bg-{{ $col }}-50 shrink-0">
                        <span class="text-2xl font-black text-{{ $col }}-700">{{ $grade }}</span>
                        <span class="text-[10px] font-semibold text-{{ $col }}-600">{{ $sc }}/100</span>
                    </div>
                    <div class="flex-1">
                        <p class="font-semibold text-gray-800">{{ $label }}</p>
                        @if($factors)
                        <div class="mt-2 grid grid-cols-2 gap-x-6 gap-y-1.5">
                            @foreach($factors as $factor => $factorData)
                            @php
                                $fScore = is_array($factorData) ? ($factorData['score'] ?? 0) : (int)$factorData;
                                $fMax   = is_array($factorData) ? ($factorData['max'] ?? 20) : 20;
                                $fLabel = is_array($factorData) ? ($factorData['label'] ?? ($factorLabels[$factor] ?? $factor)) : ($factorLabels[$factor] ?? $factor);
                            @endphp
                            <div>
                                <div class="flex justify-between text-xs text-gray-500 mb-0.5">
                                    <span>{{ $fLabel }}</span>
                                    <span class="font-semibold text-gray-700">{{ $fScore }}/{{ $fMax }}</span>
                                </div>
                                <div class="h-1.5 bg-gray-100 rounded-full">
                                    <div class="h-1.5 rounded-full {{ $fScore >= ($fMax * 0.75) ? 'bg-red-400' : ($fScore >= ($fMax * 0.5) ? 'bg-yellow-400' : 'bg-green-400') }}" style="width:{{ $fMax > 0 ? min(100, ($fScore/$fMax)*100) : 0 }}%"></div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                        @endif
                    </div>
                </div>
            </div>
            @endif

            {{-- Renewals history --}}
            @if($insurance->renewedFrom || $insurance->renewals->isNotEmpty())
            <div class="bg-white rounded-2xl border border-gray-100 p-5">
                <h2 class="font-bold text-gray-900 mb-3">Historique renouvellements</h2>
                @if($insurance->renewedFrom)
                <p class="text-sm text-gray-600 mb-2">
                    Renouvellement de :
                    <a href="{{ route('owner.insurance.show', $insurance->renewedFrom) }}" class="text-blue-600 hover:underline">
                        {{ $insurance->renewedFrom->policy_number }}
                    </a>
                </p>
                @endif
                @if($insurance->renewals->isNotEmpty())
                <div class="space-y-2">
                    @foreach($insurance->renewals as $renewed)
                    <div class="flex items-center justify-between text-sm bg-gray-50 rounded-xl px-3 py-2">
                        <a href="{{ route('owner.insurance.show', $renewed) }}" class="text-blue-600 hover:underline font-medium">{{ $renewed->policy_number }}</a>
                        <span class="text-gray-500">{{ $renewed->start_date->format('d/m/Y') }} → {{ $renewed->end_date->format('d/m/Y') }}</span>
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-{{ $renewed->status_color }}-100 text-{{ $renewed->status_color }}-700">{{ $renewed->status_label }}</span>
                    </div>
                    @endforeach
                </div>
                @endif
            </div>
            @endif

        </div>

        {{-- Right: Event Timeline --}}
        <div class="space-y-4">
            <div class="bg-white rounded-2xl border border-gray-100 p-5">
                <h2 class="font-bold text-gray-900 mb-4">Historique</h2>
                @if($insurance->events->isEmpty())
                <p class="text-sm text-gray-400 text-center py-4">Aucun événement enregistré</p>
                @else
                <div class="relative">
                    <div class="absolute left-3.5 top-0 bottom-0 w-px bg-gray-100"></div>
                    <div class="space-y-4">
                        @foreach($insurance->events as $event)
                        <div class="flex items-start gap-3 relative">
                            <div class="w-7 h-7 rounded-full bg-{{ $event->color }}-100 border-2 border-{{ $event->color }}-300 flex items-center justify-center shrink-0 z-10 text-xs">
                                {{ $event->icon }}
                            </div>
                            <div class="flex-1 min-w-0 pb-1">
                                <p class="text-sm font-semibold text-gray-800 leading-snug">{{ $event->title }}</p>
                                @if($event->description)
                                <p class="text-xs text-gray-500 mt-0.5 leading-relaxed">{{ $event->description }}</p>
                                @endif
                                <p class="text-[10px] text-gray-400 mt-1">{{ $event->created_at->format('d/m/Y H:i') }}</p>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>
        </div>

    </div>
</div>
@endsection
