@extends('layouts.owner', ['sidebarActive' => 'lease-contracts'])

@section('title', 'Nouveau contrat de bail')

@section('owner-content')
<div class="max-w-3xl space-y-6">

    {{-- Fil d'Ariane --}}
    <nav class="text-sm text-gray-400">
        <a href="{{ route('owner.lease-contracts.index') }}" class="hover:text-emerald-600">Contrats</a>
        <span class="mx-2">›</span>
        <span class="text-gray-700">Nouveau contrat</span>
    </nav>

    <h1 class="text-2xl font-bold text-gray-900">📄 Créer un contrat de bail</h1>

    @if($errors->any())
    <div class="bg-red-50 border border-red-200 rounded-xl p-4">
        <ul class="text-sm text-red-700 list-disc list-inside space-y-1">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <form method="POST" action="{{ route('owner.lease-contracts.store') }}" class="space-y-6">
        @csrf

        {{-- Parties --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 space-y-4">
            <h2 class="font-semibold text-gray-800 border-b pb-2">Parties du contrat</h2>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Résidence *</label>
                <select name="residence_id" required
                    class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:ring-emerald-500 focus:border-emerald-500 text-sm">
                    <option value="">— Choisir une résidence —</option>
                    @foreach($residences as $residence)
                        <option value="{{ $residence->id }}" {{ old('residence_id') == $residence->id ? 'selected' : '' }}>
                            {{ $residence->name }} ({{ $residence->commune }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div x-data="leaseTenantSearch({{ \Illuminate\Support\Js::encode([
                    'selectedId' => old('tenant_id'),
                    'tenants'    => $tenants->map(fn($t) => ['id' => $t->id, 'name' => $t->name, 'email' => $t->email]),
                ]) }})">
                <label class="block text-sm font-medium text-gray-700 mb-1">Locataire *</label>
                <input type="hidden" name="tenant_id" :value="selectedId" required>
                <div class="relative">
                    <input type="text" x-model="search"
                        @focus="open = true"
                        @click="open = true"
                        @input="open = true; selectedId = ''"
                        placeholder="Rechercher par nom ou email..."
                        class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:ring-emerald-500 focus:border-emerald-500 text-sm"
                        autocomplete="off">
                    <div x-show="open && filtered.length > 0" x-transition
                        @click.away="open = false"
                        class="absolute z-20 mt-1 w-full bg-white rounded-lg shadow-lg border border-gray-100 max-h-48 overflow-y-auto">
                        <template x-for="t in filtered" :key="t.id">
                            <button type="button" @click="select(t)"
                                class="w-full text-left px-3 py-2 hover:bg-emerald-50 transition text-sm flex items-center justify-between"
                                :class="selectedId == t.id ? 'bg-emerald-50' : ''">
                                <span>
                                    <span class="font-medium text-gray-900" x-text="t.name"></span>
                                    <span class="text-xs text-gray-400 ml-1" x-text="t.email"></span>
                                </span>
                                <svg x-show="selectedId == t.id" class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                            </button>
                        </template>
                    </div>
                    <div x-show="open && search && filtered.length === 0" x-transition
                        @click.away="open = false"
                        class="absolute z-20 mt-1 w-full bg-white rounded-lg shadow-lg border border-gray-100 p-3 text-sm text-gray-400 text-center">
                        Aucun locataire trouvé pour « <span x-text="search"></span> »
                    </div>
                </div>
                <p class="text-xs text-gray-400 mt-1">Recherchez parmi vos locataires passés ou actuels</p>
            </div>

            @isset($bookings)
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Réservation liée (optionnel)</label>
                <select name="booking_id"
                    class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:ring-emerald-500 focus:border-emerald-500 text-sm">
                    <option value="">— Aucune réservation —</option>
                    @foreach($bookings as $booking)
                        <option value="{{ $booking->id }}" {{ old('booking_id') == $booking->id ? 'selected' : '' }}>
                            #{{ $booking->id }} — {{ $booking->guest->name }} ({{ $booking->check_in->format('d/m/Y') }})
                        </option>
                    @endforeach
                </select>
            </div>
            @endisset
        </div>

        {{-- Durée du bail --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 space-y-4">
            <h2 class="font-semibold text-gray-800 border-b pb-2">Durée et type de bail</h2>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Type de bail *</label>
                <select name="lease_type" required
                    class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:ring-emerald-500 focus:border-emerald-500 text-sm">
                    <option value="short_term" {{ old('lease_type', 'short_term') === 'short_term' ? 'selected' : '' }}>Court terme — par nuit</option>
                    <option value="monthly" {{ old('lease_type') === 'monthly' ? 'selected' : '' }}>Mensuel (mois par mois)</option>
                    <option value="fixed_term" {{ old('lease_type') === 'fixed_term' ? 'selected' : '' }}>Durée déterminée (longue durée)</option>
                </select>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Date d'entrée *</label>
                    <input type="date" name="start_date" value="{{ old('start_date') }}" required
                        class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:ring-emerald-500 focus:border-emerald-500 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Date de fin (optionnel)</label>
                    <input type="date" name="end_date" value="{{ old('end_date') }}"
                        class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:ring-emerald-500 focus:border-emerald-500 text-sm">
                </div>
            </div>
        </div>

        {{-- Conditions financières --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 space-y-4"
            x-data="leaseTypeSection({{ \Illuminate\Support\Js::encode(['leaseType' => old('lease_type', 'short_term')]) }})">
            <h2 class="font-semibold text-gray-800 border-b pb-2">Conditions financières</h2>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        <span x-show="leaseType === 'short_term'">Tarif par nuit (FCFA) *</span>
                        <span x-show="leaseType !== 'short_term'">Montant mensuel de location (FCFA) *</span>
                    </label>
                    <input type="number" name="monthly_rent" value="{{ old('monthly_rent') }}" required min="0" step="500"
                        class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:ring-emerald-500 focus:border-emerald-500 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Dépôt de garantie (FCFA) *</label>
                    <input type="number" name="deposit_amount" value="{{ old('deposit_amount') }}" required min="0" step="500"
                        class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:ring-emerald-500 focus:border-emerald-500 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        <span x-show="leaseType === 'short_term'">Frais de ménage (FCFA)</span>
                        <span x-show="leaseType !== 'short_term'">Charges mensuelles (FCFA)</span>
                    </label>
                    <input type="number" name="charges_amount" value="{{ old('charges_amount', 0) }}" min="0" step="500"
                        class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:ring-emerald-500 focus:border-emerald-500 text-sm">
                </div>
                <div x-show="leaseType !== 'short_term'">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Jour d'échéance du paiement</label>
                    <input type="number" name="payment_day" value="{{ old('payment_day', 5) }}" min="1" max="28"
                        class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:ring-emerald-500 focus:border-emerald-500 text-sm"
                        placeholder="Ex: 5 (pour le 5 du mois)">
                </div>
            </div>
        </div>

        {{-- Clauses et services avec IA --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 space-y-4"
            x-data="leaseClausesSection({{ \Illuminate\Support\Js::encode([
                'services'    => old('included_services', []),
                'clauses'     => old('special_clauses', ''),
                'generateUrl' => route('owner.ai.generate-clauses'),
                'suggestUrl'  => route('owner.ai.suggest-services'),
                'csrfToken'   => csrf_token(),
            ]) }})">
            <div class="flex items-center justify-between border-b pb-2">
                <h2 class="font-semibold text-gray-800">Clauses et services (optionnel)</h2>
                <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-violet-100 text-violet-700 rounded-full text-[10px] font-bold uppercase tracking-wide">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    IA disponible
                </span>
            </div>

            {{-- Erreur IA --}}
            <div x-show="aiError" x-cloak x-transition class="bg-red-50 border border-red-200 text-red-600 text-xs rounded-lg px-3 py-2">
                <span x-text="aiError"></span>
            </div>

            {{-- Services inclus --}}
            <div>
                <div class="flex items-center justify-between mb-1">
                    <label class="block text-sm font-medium text-gray-700">Services inclus dans la location</label>
                    <button type="button" @click="suggestServices()" :disabled="aiServicesLoading"
                        class="inline-flex items-center gap-1 px-2.5 py-1 bg-violet-50 text-violet-700 rounded-lg text-xs font-medium hover:bg-violet-100 transition disabled:opacity-50">
                        <svg x-show="!aiServicesLoading" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        <svg x-show="aiServicesLoading" class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                        <span x-text="aiServicesLoading ? 'Suggestion...' : 'Suggérer par IA'"></span>
                    </button>
                </div>
                <div class="flex gap-2 mb-2">
                    <input type="text" x-model="newService" @keydown.enter.prevent="add()"
                        placeholder="Ex: Électricité, Eau, Wifi, Gardiennage..."
                        class="flex-1 px-3 py-2 border border-gray-200 rounded-lg focus:ring-emerald-500 focus:border-emerald-500 text-sm">
                    <button type="button" @click="add()"
                        class="px-3 py-2 bg-emerald-100 text-emerald-700 rounded-lg text-sm font-medium hover:bg-emerald-200 transition">
                        + Ajouter
                    </button>
                </div>
                <div class="flex flex-wrap gap-2">
                    <template x-for="(service, i) in services" :key="i">
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-emerald-50 text-emerald-700 rounded-full text-xs font-medium">
                            <input type="hidden" name="included_services[]" :value="service">
                            <span x-text="service"></span>
                            <button type="button" @click="remove(i)" class="hover:text-red-500 transition">&times;</button>
                        </span>
                    </template>
                </div>
            </div>

            {{-- Clauses spéciales --}}
            <div>
                <div class="flex items-center justify-between mb-1">
                    <label class="block text-sm font-medium text-gray-700">Clauses particulières</label>
                    <button type="button" @click="generateClauses()" :disabled="aiLoading"
                        class="inline-flex items-center gap-1 px-2.5 py-1 bg-violet-50 text-violet-700 rounded-lg text-xs font-medium hover:bg-violet-100 transition disabled:opacity-50">
                        <svg x-show="!aiLoading" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        <svg x-show="aiLoading" class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                        <span x-text="aiLoading ? 'Génération...' : 'Générer par IA'"></span>
                    </button>
                </div>
                <textarea name="special_clauses" rows="6" x-model="clauses"
                    class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:ring-emerald-500 focus:border-emerald-500 text-sm"
                    placeholder="Conditions spéciales, restrictions, règles particulières..."></textarea>
                <p class="text-xs text-gray-400 mt-1">L'IA génère des clauses basées sur le type de bail, le montant de location et les services — vous pouvez les modifier librement.</p>
            </div>
        </div>

        {{-- Actions --}}
        <div class="flex gap-3">
            <button type="submit"
                class="flex-1 py-3 bg-emerald-600 text-white rounded-xl font-semibold hover:bg-emerald-700 transition">
                Créer le contrat
            </button>
            <a href="{{ route('owner.lease-contracts.index') }}"
                class="px-6 py-3 border border-gray-200 rounded-xl font-semibold text-gray-600 hover:bg-gray-50 transition">
                Annuler
            </a>
        </div>
    </form>
</div>
@endsection
