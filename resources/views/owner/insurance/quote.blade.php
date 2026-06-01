@extends('layouts.owner')

@section('title', 'Simulateur de devis — REZI')

@section('owner-content')
<div class="max-w-4xl mx-auto space-y-6">

    {{-- Header --}}
    <div class="flex items-center gap-3">
        <a href="{{ route('owner.insurance.index') }}" class="text-gray-400 hover:text-gray-600">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/></svg>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Simulateur de devis</h1>
            <p class="text-sm text-gray-500">Obtenez une estimation personnalisée selon le profil de risque de votre résidence</p>
        </div>
    </div>

    {{-- Selector --}}
    <div class="bg-white rounded-2xl border border-gray-100 p-5">
        <form method="GET" class="flex items-end gap-3">
            <div class="flex-1">
                <label class="block text-sm font-semibold text-gray-700 mb-1.5">Choisir une résidence</label>
                <select name="residence_id" class="w-full rounded-xl border-gray-200 text-sm focus:ring-[#F16A00] focus:border-[#F16A00]">
                    <option value="">— Sélectionner une résidence —</option>
                    @foreach($residences as $r)
                    <option value="{{ $r->id }}" {{ request('residence_id') == $r->id ? 'selected' : '' }}>{{ $r->name }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="px-5 py-2.5 bg-gray-900 text-white font-semibold rounded-xl hover:bg-gray-800 text-sm shrink-0">
                Calculer
            </button>
        </form>
    </div>

    @if($residence && $quote)

    {{-- Risk Score Card --}}
    @php
        $sampleQuote = $quote[\App\Models\InsuranceSubscription::TYPE_STANDARD]
                    ?? reset($quote);
        $riskScore   = $sampleQuote['risk_score'] ?? null;
        $riskGrade   = $sampleQuote['risk_grade'] ?? '—';
        $riskLabel   = $sampleQuote['risk_label'] ?? '';
        $riskFactors = $sampleQuote['risk_factors'] ?? [];
        $riskMult    = $sampleQuote['risk_multiplier'] ?? 1.0;
        $riskCol     = match(true) {
            ($riskScore ?? 0) <= 20 => 'green',
            ($riskScore ?? 0) <= 35 => 'emerald',
            ($riskScore ?? 0) <= 50 => 'yellow',
            ($riskScore ?? 0) <= 65 => 'orange',
            default                 => 'red',
        };

        $planFeatures = [
            'basic'    => ['Incendie & explosion', 'Dégâts des eaux', 'Responsabilité civile locataire', 'Franchise ' . number_format(50000, 0, ',', ' ') . ' FCFA'],
            'standard' => ['Tout risque incendie', 'Dégâts des eaux & vandalisme', 'Vol & cambriolage', 'RC locataire + voisins recours', 'Franchise ' . number_format(25000, 0, ',', ' ') . ' FCFA'],
            'premium'  => ['Couverture tous risques', 'Vol, bris de glace, vandalisme', 'Perte de revenus de location (3 mois)', 'RC étendue tous tiers', 'Expertise privée incluse', 'Franchise ' . number_format(10000, 0, ',', ' ') . ' FCFA'],
        ];
        $planDescriptions = [
            'basic'    => 'Protection essentielle contre les sinistres courants',
            'standard' => 'Couverture complète recommandée pour la location',
            'premium'  => 'Protection maximale avec garanties étendues',
        ];
    @endphp

    <div class="bg-white rounded-2xl border border-gray-100 p-5">
        <h2 class="font-bold text-gray-900 mb-4">Score de risque — {{ $residence->name }}</h2>
        <div class="flex items-center gap-6">
            {{-- Score Circle --}}
            <div class="shrink-0 w-24 h-24 rounded-full border-4 border-{{ $riskCol }}-400 flex flex-col items-center justify-center bg-{{ $riskCol }}-50">
                <span class="text-3xl font-black text-{{ $riskCol }}-700">{{ $riskGrade }}</span>
                <span class="text-xs font-semibold text-{{ $riskCol }}-600">{{ $riskScore }}/100</span>
            </div>
            <div class="flex-1">
                <p class="font-semibold text-gray-800">{{ $riskLabel }}</p>
                <p class="text-sm text-gray-500 mt-0.5">Multiplicateur de prime : <strong>×{{ number_format($riskMult, 2) }}</strong></p>
                {{-- Factor bars --}}
                @if($riskFactors)
                <div class="mt-3 grid grid-cols-2 gap-x-6 gap-y-1.5">
                    @foreach($riskFactors as $factor => $factorData)
                    @php
                        $fScore = is_array($factorData) ? ($factorData['score'] ?? 0) : (int)$factorData;
                        $fMax   = is_array($factorData) ? ($factorData['max'] ?? 20) : 20;
                        $fLabel = is_array($factorData) ? ($factorData['label'] ?? $factor) : $factor;
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

    {{-- Plans Comparison --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        @foreach($quote as $type => $plan)
        @php
            $accent = match($type) {
                'basic'   => 'slate',
                'premium' => 'orange',
                default   => 'blue',
            };
            $isRecommended = $type === 'standard';
        @endphp
        <div class="bg-white rounded-2xl border-2 {{ $isRecommended ? 'border-blue-500 ring-2 ring-blue-500/20' : 'border-gray-100' }} p-5 relative flex flex-col">
            @if($isRecommended)
            <span class="absolute -top-3 left-1/2 -translate-x-1/2 px-3 py-0.5 bg-blue-500 text-white text-[10px] font-bold rounded-full uppercase tracking-wide">Recommandé</span>
            @endif

            <div class="mb-4">
                <h3 class="font-bold text-gray-900 text-lg">{{ $plan['coverage_label'] }}</h3>
                <p class="text-xs text-gray-400 mt-0.5">{{ $planDescriptions[$type] ?? '' }}</p>
            </div>

            <div class="mb-4">
                <p class="text-3xl font-black text-gray-900">{{ number_format($plan['suggested_premium'], 0, ',', ' ') }}</p>
                <p class="text-xs text-gray-500">FCFA / mois</p>
                <p class="text-xs text-gray-400 mt-1">{{ number_format($plan['annual_cost'], 0, ',', ' ') }} FCFA / an</p>
                <p class="text-xs text-gray-400">Capital assuré max : {{ number_format($plan['max_coverage'], 0, ',', ' ') }} FCFA</p>
            </div>

            <ul class="space-y-1.5 text-xs text-gray-600 mb-5 flex-1">
                @foreach($planFeatures[$type] ?? [] as $feature)
                <li class="flex items-start gap-1.5">
                    <svg class="w-3.5 h-3.5 text-green-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                    {{ $feature }}
                </li>
                @endforeach
            </ul>

            {{-- Breakdown --}}
            @if(!empty($plan['loading_breakdown']))
            <details class="mb-4">
                <summary class="text-xs text-gray-400 cursor-pointer hover:text-gray-600">Détail du calcul ▾</summary>
                <div class="mt-2 space-y-1 text-xs">
                    <div class="flex justify-between text-gray-500">
                        <span>Prime pure (risque)</span>
                        <span>{{ number_format($plan['pure_premium'], 0, ',', ' ') }}</span>
                    </div>
                    @foreach($plan['loading_breakdown'] as $loading)
                    <div class="flex justify-between text-gray-500">
                        <span>{{ $loading['label'] }} ({{ $loading['rate'] }})</span>
                        <span>{{ number_format($loading['amount'], 0, ',', ' ') }}</span>
                    </div>
                    @endforeach
                    <div class="flex justify-between font-semibold text-gray-700 border-t pt-1">
                        <span>Prime mensuelle</span>
                        <span>{{ number_format($plan['suggested_premium'], 0, ',', ' ') }}</span>
                    </div>
                </div>
            </details>
            @endif

            <a href="{{ route('owner.insurance.create') }}?coverage_type={{ $type }}&residence_id={{ $residence->id }}&suggested_premium={{ $plan['suggested_premium'] }}"
               class="block text-center py-2.5 rounded-xl text-sm font-semibold transition-all
               {{ $isRecommended ? 'bg-blue-600 text-white hover:bg-blue-700' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                Souscrire ce plan
            </a>
        </div>
        @endforeach
    </div>

    <p class="text-xs text-gray-400 text-center">
        * Simulation indicative basée sur le modèle actuariel CIMA. La prime finale est déterminée par l'assureur.
    </p>

    @elseif(request('residence_id') && !$residence)
    <div class="bg-red-50 border border-red-200 rounded-xl p-4 text-sm text-red-700">
        Résidence introuvable ou non autorisée.
    </div>
    @endif

</div>
@endsection
