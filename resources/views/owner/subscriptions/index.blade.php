@extends('layouts.owner')

@section('title', 'Abonnements - REZI')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    {{-- Flash Messages --}}
    @if(session('success'))
        <div class="mb-6 rounded-lg bg-green-50 p-4 border border-green-200">
            <div class="flex">
                <svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                <p class="ml-3 text-sm text-green-700">{{ session('success') }}</p>
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="mb-6 rounded-lg bg-red-50 p-4 border border-red-200">
            <div class="flex">
                <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                </svg>
                <p class="ml-3 text-sm text-red-700">{{ session('error') }}</p>
            </div>
        </div>
    @endif

    {{-- Header --}}
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Abonnements</h1>
        <p class="mt-2 text-gray-600">Choisissez le plan qui correspond à vos besoins</p>
    </div>

    {{-- Current Subscription --}}
    @if($currentSubscription)
        <div class="mb-8 bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">Votre abonnement actuel</h2>
                    <p class="text-gray-600 mt-1">
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-emerald-100 text-emerald-800">
                            {{ $currentSubscription->plan->name }}
                        </span>
                        <span class="ml-2 text-sm">
                            @if($currentSubscription->status === 'active')
                                Actif jusqu'au {{ $currentSubscription->current_period_end->format('d/m/Y') }}
                            @elseif($currentSubscription->status === 'cancelled')
                                Annulé - Actif jusqu'au {{ $currentSubscription->current_period_end->format('d/m/Y') }}
                            @endif
                        </span>
                    </p>
                </div>
                <div class="flex gap-3">
                    <a href="{{ route('owner.subscriptions.history') }}" class="btn-secondary">
                        Historique
                    </a>
                    @if($currentSubscription->status === 'active')
                        <form action="{{ route('owner.subscriptions.cancel') }}" method="POST" class="inline"
                              onsubmit="return confirm('Êtes-vous sûr de vouloir annuler votre abonnement ?')">
                            @csrf
                            <button type="submit" class="btn-danger">Annuler</button>
                        </form>
                    @elseif($currentSubscription->status === 'cancelled')
                        <form action="{{ route('owner.subscriptions.resume') }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="btn-primary">Réactiver</button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    @endif

    {{-- Plans Grid --}}
    <div class="grid md:grid-cols-3 gap-6">
        @foreach($plans as $plan)
            <div class="bg-white rounded-xl shadow-sm border {{ $currentSubscription?->plan_id === $plan->id ? 'border-emerald-500 ring-2 ring-emerald-500' : 'border-gray-200' }} overflow-hidden">
                {{-- Plan Header --}}
                <div class="p-6 {{ $plan->is_featured ? 'bg-linear-to-r from-emerald-500 to-teal-500 text-white' : '' }}">
                    @if($plan->is_featured)
                        <span class="inline-block px-3 py-1 text-xs font-semibold bg-white/20 rounded-full mb-3">
                            Le plus populaire
                        </span>
                    @endif
                    <h3 class="text-xl font-bold {{ $plan->is_featured ? 'text-white' : 'text-gray-900' }}">
                        {{ $plan->name }}
                    </h3>
                    <p class="mt-2 text-sm {{ $plan->is_featured ? 'text-white/80' : 'text-gray-500' }}">
                        {{ $plan->description }}
                    </p>
                </div>

                {{-- Pricing --}}
                <div class="p-6 border-b border-gray-100">
                    <div class="flex items-baseline">
                        <span class="text-4xl font-bold text-gray-900">
                            {{ number_format($plan->monthly_price, 0, ',', ' ') }}
                        </span>
                        <span class="ml-1 text-gray-500">FCFA/mois</span>
                    </div>
                    @if($plan->yearly_price)
                        <p class="mt-1 text-sm text-gray-500">
                            ou {{ number_format($plan->yearly_price, 0, ',', ' ') }} FCFA/an
                            <span class="text-emerald-600 font-medium">
                                (-{{ round((1 - ($plan->yearly_price / ($plan->monthly_price * 12))) * 100) }}%)
                            </span>
                        </p>
                    @endif
                </div>

                {{-- Features --}}
                <div class="p-6">
                    <ul class="space-y-3">
                        <li class="flex items-start">
                            <svg class="h-5 w-5 text-emerald-500 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            <span class="ml-3 text-gray-600">
                                @if($plan->max_residences === -1)
                                    Annonces illimitées
                                @else
                                    Jusqu'à {{ $plan->max_residences }} annonce(s)
                                @endif
                            </span>
                        </li>
                        <li class="flex items-start">
                            <svg class="h-5 w-5 text-emerald-500 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            <span class="ml-3 text-gray-600">
                                @if($plan->max_photos_per_residence === -1)
                                    Photos illimitées
                                @else
                                    {{ $plan->max_photos_per_residence }} photos par annonce
                                @endif
                            </span>
                        </li>
                        @if($plan->features)
                            @foreach($plan->features as $feature)
                                <li class="flex items-start">
                                    <svg class="h-5 w-5 text-emerald-500 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                    </svg>
                                    <span class="ml-3 text-gray-600">{{ $feature }}</span>
                                </li>
                            @endforeach
                        @endif
                    </ul>
                </div>

                {{-- CTA --}}
                <div class="p-6 bg-gray-50">
                    @if($currentSubscription?->plan_id === $plan->id)
                        <button disabled class="w-full py-3 px-4 rounded-lg bg-gray-300 text-gray-500 font-semibold cursor-not-allowed">
                            Plan actuel
                        </button>
                    @else
                        <button type="button" 
                                onclick="openPaymentModal({{ $plan->id }}, '{{ $plan->name }}', {{ $plan->monthly_price }}, {{ $plan->yearly_price ?? 0 }})"
                                class="w-full py-3 px-4 rounded-lg {{ $plan->is_featured ? 'bg-emerald-600 hover:bg-emerald-700' : 'bg-gray-900 hover:bg-gray-800' }} text-white font-semibold transition-colors">
                            @if($currentSubscription)
                                Changer de plan
                            @else
                                Souscrire
                            @endif
                        </button>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    {{-- FAQ --}}
    <div class="mt-12 bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <h2 class="text-xl font-bold text-gray-900 mb-6">Questions fréquentes</h2>
        <div class="space-y-4">
            <details class="group">
                <summary class="cursor-pointer flex items-center justify-between py-3 font-medium text-gray-900">
                    Comment fonctionne la facturation ?
                    <svg class="h-5 w-5 text-gray-500 group-open:rotate-180 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </summary>
                <p class="py-3 text-gray-600">
                    Vous êtes facturé au début de chaque période (mensuelle ou annuelle). Le paiement est effectué via Mobile Money (Wave, Orange Money, MTN MoMo, etc.).
                </p>
            </details>
            <details class="group">
                <summary class="cursor-pointer flex items-center justify-between py-3 font-medium text-gray-900">
                    Puis-je changer de plan à tout moment ?
                    <svg class="h-5 w-5 text-gray-500 group-open:rotate-180 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </summary>
                <p class="py-3 text-gray-600">
                    Oui ! Vous pouvez passer à un plan supérieur à tout moment. La différence de prix sera calculée au prorata. Pour passer à un plan inférieur, le changement prendra effet à la fin de votre période actuelle.
                </p>
            </details>
            <details class="group">
                <summary class="cursor-pointer flex items-center justify-between py-3 font-medium text-gray-900">
                    Comment annuler mon abonnement ?
                    <svg class="h-5 w-5 text-gray-500 group-open:rotate-180 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </summary>
                <p class="py-3 text-gray-600">
                    Vous pouvez annuler à tout moment depuis cette page. Votre abonnement restera actif jusqu'à la fin de la période payée. Vous pouvez réactiver à tout moment avant la fin de cette période.
                </p>
            </details>
        </div>
    </div>
</div>

{{-- Payment Modal --}}
<div id="paymentModal" class="fixed inset-0 z-50 hidden" x-data="{ open: false }">
    <div class="fixed inset-0 bg-black/50 transition-opacity" onclick="closePaymentModal()"></div>
    <div class="fixed inset-0 flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-xl max-w-md w-full p-6 relative">
            <button onclick="closePaymentModal()" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>

            <h3 class="text-xl font-bold text-gray-900 mb-4">Souscrire à <span id="modalPlanName"></span></h3>

            <form id="subscribeForm" method="POST" action="">
                @csrf

                {{-- Billing Period --}}
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-3">Période de facturation</label>
                    <div class="grid grid-cols-2 gap-3">
                        <label class="relative cursor-pointer">
                            <input type="radio" name="billing_period" value="monthly" class="peer sr-only" checked>
                            <div class="p-4 border-2 rounded-lg peer-checked:border-emerald-500 peer-checked:bg-emerald-50 transition-colors">
                                <p class="font-semibold text-gray-900">Mensuel</p>
                                <p class="text-sm text-gray-500" id="monthlyPrice"></p>
                            </div>
                        </label>
                        <label class="relative cursor-pointer" id="yearlyOption">
                            <input type="radio" name="billing_period" value="yearly" class="peer sr-only">
                            <div class="p-4 border-2 rounded-lg peer-checked:border-emerald-500 peer-checked:bg-emerald-50 transition-colors">
                                <p class="font-semibold text-gray-900">Annuel</p>
                                <p class="text-sm text-gray-500" id="yearlyPrice"></p>
                                <span class="absolute -top-2 -right-2 px-2 py-0.5 text-xs font-bold bg-emerald-500 text-white rounded-full" id="yearlyDiscount"></span>
                            </div>
                        </label>
                    </div>
                </div>

                {{-- Payment Method --}}
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-3">Mode de paiement</label>
                    <div class="grid grid-cols-3 gap-3">
                        @foreach(['wave' => 'Wave', 'orange' => 'Orange Money', 'mtn' => 'MTN MoMo', 'moov' => 'Moov Money', 'djamo' => 'Djamo'] as $code => $label)
                            <label class="relative cursor-pointer">
                                <input type="radio" name="payment_method" value="{{ $code }}" class="peer sr-only" {{ $loop->first ? 'checked' : '' }}>
                                <div class="p-3 border-2 rounded-lg peer-checked:border-emerald-500 peer-checked:bg-emerald-50 transition-colors text-center">
                                    <p class="text-sm font-medium text-gray-900">{{ $label }}</p>
                                </div>
                            </label>
                        @endforeach
                    </div>
                </div>

                <button type="submit" class="w-full py-3 px-4 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-semibold transition-colors">
                    Procéder au paiement
                </button>
            </form>
        </div>
    </div>
</div>

<style>
    .btn-primary {
        @apply inline-flex items-center px-4 py-2 bg-emerald-600 text-white font-medium rounded-lg hover:bg-emerald-700 transition-colors;
    }
    .btn-secondary {
        @apply inline-flex items-center px-4 py-2 bg-white border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-50 transition-colors;
    }
    .btn-danger {
        @apply inline-flex items-center px-4 py-2 bg-red-600 text-white font-medium rounded-lg hover:bg-red-700 transition-colors;
    }
</style>

<script>
    let currentPlanId = null;

    function openPaymentModal(planId, planName, monthlyPrice, yearlyPrice) {
        currentPlanId = planId;
        document.getElementById('modalPlanName').textContent = planName;
        document.getElementById('monthlyPrice').textContent = new Intl.NumberFormat('fr-FR').format(monthlyPrice) + ' FCFA';
        
        const yearlyOption = document.getElementById('yearlyOption');
        if (yearlyPrice > 0) {
            yearlyOption.classList.remove('hidden');
            document.getElementById('yearlyPrice').textContent = new Intl.NumberFormat('fr-FR').format(yearlyPrice) + ' FCFA';
            const discount = Math.round((1 - (yearlyPrice / (monthlyPrice * 12))) * 100);
            document.getElementById('yearlyDiscount').textContent = '-' + discount + '%';
        } else {
            yearlyOption.classList.add('hidden');
        }

        // Set form action based on current subscription
        const hasSubscription = {{ $currentSubscription ? 'true' : 'false' }};
        const formAction = hasSubscription 
            ? '{{ route("owner.subscriptions.change-plan", ":id") }}'.replace(':id', planId)
            : '{{ route("owner.subscriptions.subscribe", ":id") }}'.replace(':id', planId);
        document.getElementById('subscribeForm').action = formAction;

        document.getElementById('paymentModal').classList.remove('hidden');
    }

    function closePaymentModal() {
        document.getElementById('paymentModal').classList.add('hidden');
    }
</script>
@endsection
