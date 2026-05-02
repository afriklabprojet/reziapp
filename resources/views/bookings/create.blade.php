@extends('layouts.app')

@section('title', ($residence->instant_book ? 'Confirmer et payer' : 'Demander une réservation') . ' - ' .
    $residence->name)

@section('content')
    @php
        $owner = $residence->owner;
        $policy = $residence->cancellationPolicy;
        $photo = $residence->photos->first();
        $isInstant = (bool) $residence->instant_book;
        $houseRules = $residence->house_rules ?? [];
    @endphp

    <div class="min-h-screen bg-white" x-data="bookingCreateForm(@js([
    'maxGuests' => $residence->max_guests ?? 10,
    'minNights' => $residence->min_nights ?? 1,
    'maxNights' => $residence->max_nights ?? 365,
    'calendar' => $calendar,
    'residenceId' => $residence->id,
    'csrfToken' => csrf_token(),
    'isInstant' => $isInstant,
    'pricePerNight' => (float) $pricePerNight,
    'checkInInit' => $checkIn?->format('Y-m-d') ?? '',
    'checkOutInit' => $checkOut?->format('Y-m-d') ?? '',
    'adultsInit' => $adults ?? 1,
    'childrenInit' => $children ?? 0,
    'infantsInit' => $infants ?? 0,
    'pricePreview' => $pricePreview,
]))">

        {{-- ════════════════════════════════════════════
             HEADER — Bouton retour style Airbnb
        ════════════════════════════════════════════ --}}
        <div class="border-b border-gray-200 sticky top-14 md:top-0 bg-white/95 backdrop-blur-sm z-20">
            <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex items-center">
                <a href="{{ route('residences.show', $residence) }}"
                    class="mr-4 p-2 -ml-2 min-w-11 min-h-11 flex items-center justify-center rounded-full hover:bg-gray-100 transition-colors"
                    aria-label="Retour">
                    <svg aria-hidden="true" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </a>
                <h1 class="text-xl sm:text-2xl font-semibold text-gray-900">
                    {{ $isInstant ? 'Confirmer et payer' : 'Demander une réservation' }}
                </h1>
            </div>
        </div>

        {{-- ════════════════════════════════════════════
             BODY — 2 colonnes
        ════════════════════════════════════════════ --}}
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="flex flex-col-reverse lg:flex-row gap-8 lg:gap-12">

                {{-- ═══ COLONNE GAUCHE — Formulaire ═══ --}}
                <div class="flex-1 min-w-0 space-y-8">

                    {{-- ─── SECTION 1 : Votre voyage ─── --}}
                    <section>
                        <h2 class="text-[22px] font-semibold text-gray-900 mb-6">Votre voyage</h2>

                        {{-- Dates --}}
                        <div class="flex items-start justify-between py-4">
                            <div>
                                <h3 class="font-medium text-gray-900">Dates</h3>
                                <p class="text-gray-500 text-sm mt-0.5" x-text="datesLabel"></p>
                            </div>
                            <button type="button" @click="editingDates = !editingDates"
                                class="text-sm font-semibold underline hover:text-gray-600 shrink-0 min-h-11 min-w-11 flex items-center justify-center">
                                Modifier
                            </button>
                        </div>

                        {{-- Éditeur inline de dates --}}
                        <div x-show="editingDates" x-collapse x-cloak class="pb-6 border-b border-gray-200">
                            <div class="bg-gray-50 rounded-xl p-4">
                                {{-- Navigation mois --}}
                                <div class="flex items-center justify-between mb-3">
                                    <button @click="previousMonth()" type="button"
                                        class="p-2.5 min-w-11 min-h-11 flex items-center justify-center hover:bg-gray-200 rounded-full transition-colors active:bg-gray-300">
                                        <svg aria-hidden="true" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M15 19l-7-7 7-7" />
                                        </svg>
                                    </button>
                                    <span x-text="currentMonthName" class="font-semibold text-sm"></span>
                                    <button @click="nextMonth()" type="button"
                                        class="p-2.5 min-w-11 min-h-11 flex items-center justify-center hover:bg-gray-200 rounded-full transition-colors active:bg-gray-300">
                                        <svg aria-hidden="true" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 5l7 7-7 7" />
                                        </svg>
                                    </button>
                                </div>

                                {{-- Jours semaine --}}
                                <div class="grid grid-cols-7 gap-0 text-center text-xs text-gray-400 mb-1">
                                    <span>Lu</span><span>Ma</span><span>Me</span><span>Je</span><span>Ve</span><span>Sa</span><span>Di</span>
                                </div>

                                {{-- Grille jours --}}
                                <div class="grid grid-cols-7 gap-0">
                                    <template x-for="day in calendarDays" :key="day.key">
                                        <button type="button"
                                            @click="day.available && !day.isEmpty && selectDate(day.date)"
                                            :disabled="!day.available || day.isEmpty"
                                            :class="{
                                                'invisible': day.isEmpty,
                                                'text-gray-300 cursor-not-allowed line-through': !day.available && !day
                                                    .isEmpty,
                                                'hover:bg-gray-200 active:bg-gray-300': day.available && !
                                                    isDateSelected(day.date) && !
                                                    isDateInRange(day.date),
                                                'bg-gray-900 text-white rounded-full': isDateSelected(day.date),
                                                'bg-gray-100': isDateInRange(day.date) && !isDateSelected(day.date),
                                            }"
                                            class="min-h-11 p-1 text-sm text-center transition-colors flex items-center justify-center">
                                            <span x-text="day.dayOfMonth"></span>
                                        </button>
                                    </template>
                                </div>

                                {{-- Légende --}}
                                <div class="flex items-center gap-4 mt-3 text-xs text-gray-400">
                                    <span class="flex items-center gap-1">
                                        <span class="w-2.5 h-2.5 bg-gray-900 rounded-full"></span> Sélectionné
                                    </span>
                                    <span class="flex items-center gap-1">
                                        <span class="w-2.5 h-2.5 bg-gray-100 rounded-full border border-gray-200"></span>
                                        Période
                                    </span>
                                </div>
                            </div>

                            <button type="button" @click="editingDates = false"
                                class="mt-3 text-sm font-semibold underline hover:text-gray-600 min-h-11 px-2 inline-flex items-center">
                                Fermer
                            </button>
                        </div>

                        {{-- Voyageurs --}}
                        <div class="flex items-start justify-between py-4 border-b border-gray-200">
                            <div>
                                <h3 class="font-medium text-gray-900">Voyageurs</h3>
                                <p class="text-gray-500 text-sm mt-0.5" x-text="guestsLabel"></p>
                            </div>
                            <button type="button" @click="editingGuests = !editingGuests"
                                class="text-sm font-semibold underline hover:text-gray-600 shrink-0 min-h-11 min-w-11 flex items-center justify-center">
                                Modifier
                            </button>
                        </div>

                        {{-- Éditeur inline voyageurs --}}
                        <div x-show="editingGuests" x-collapse x-cloak class="pb-6 border-b border-gray-200">
                            <div class="space-y-4 pt-2">
                                {{-- Adultes --}}
                                <div class="flex items-center justify-between">
                                    <div>
                                        <span class="font-medium text-sm">Adultes</span>
                                        <span class="text-gray-400 text-xs block">13 ans et +</span>
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <button type="button" @click="adults > 1 && adults--; syncGuests()"
                                            :disabled="adults <= 1"
                                            :class="adults <= 1 ? 'opacity-30 cursor-not-allowed' : 'hover:border-gray-900'"
                                            class="w-10 h-10 rounded-full border border-gray-300 flex items-center justify-center text-gray-600 transition-colors active:bg-gray-100">
                                            <svg aria-hidden="true" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-width="2" d="M20 12H4" />
                                            </svg>
                                        </button>
                                        <span x-text="adults" class="w-8 text-center text-sm font-medium"></span>
                                        <button type="button" @click="adults < maxGuests && adults++; syncGuests()"
                                            :disabled="(adults + children) >= maxGuests"
                                            :class="(adults + children) >= maxGuests ? 'opacity-30 cursor-not-allowed' :
                                                'hover:border-gray-900'"
                                            class="w-10 h-10 rounded-full border border-gray-300 flex items-center justify-center text-gray-600 transition-colors active:bg-gray-100">
                                            <svg aria-hidden="true" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                                {{-- Enfants --}}
                                <div class="flex items-center justify-between">
                                    <div>
                                        <span class="font-medium text-sm">Enfants</span>
                                        <span class="text-gray-400 text-xs block">2 – 12 ans</span>
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <button type="button" @click="children > 0 && children--; syncGuests()"
                                            :disabled="children <= 0"
                                            :class="children <= 0 ? 'opacity-30 cursor-not-allowed' : 'hover:border-gray-900'"
                                            class="w-10 h-10 rounded-full border border-gray-300 flex items-center justify-center text-gray-600 transition-colors active:bg-gray-100">
                                            <svg aria-hidden="true" class="w-4 h-4" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-width="2" d="M20 12H4" />
                                            </svg>
                                        </button>
                                        <span x-text="children" class="w-8 text-center text-sm font-medium"></span>
                                        <button type="button"
                                            @click="(adults + children) < maxGuests && children++; syncGuests()"
                                            :disabled="(adults + children) >= maxGuests"
                                            :class="(adults + children) >= maxGuests ? 'opacity-30 cursor-not-allowed' :
                                                'hover:border-gray-900'"
                                            class="w-10 h-10 rounded-full border border-gray-300 flex items-center justify-center text-gray-600 transition-colors active:bg-gray-100">
                                            <svg aria-hidden="true" class="w-4 h-4" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                                {{-- Bébés --}}
                                <div class="flex items-center justify-between">
                                    <div>
                                        <span class="font-medium text-sm">Bébés</span>
                                        <span class="text-gray-400 text-xs block">Moins de 2 ans</span>
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <button type="button" @click="infants > 0 && infants--" :disabled="infants <= 0"
                                            :class="infants <= 0 ? 'opacity-30 cursor-not-allowed' : 'hover:border-gray-900'"
                                            class="w-10 h-10 rounded-full border border-gray-300 flex items-center justify-center text-gray-600 transition-colors active:bg-gray-100">
                                            <svg aria-hidden="true" class="w-4 h-4" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-width="2" d="M20 12H4" />
                                            </svg>
                                        </button>
                                        <span x-text="infants" class="w-8 text-center text-sm font-medium"></span>
                                        <button type="button" @click="infants < 5 && infants++" :disabled="infants >= 5"
                                            :class="infants >= 5 ? 'opacity-30 cursor-not-allowed' : 'hover:border-gray-900'"
                                            class="w-10 h-10 rounded-full border border-gray-300 flex items-center justify-center text-gray-600 transition-colors active:bg-gray-100">
                                            <svg aria-hidden="true" class="w-4 h-4" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <p class="text-xs text-gray-400 mt-3">
                                Ce logement peut accueillir {{ $residence->max_guests }}
                                voyageur{{ $residence->max_guests > 1 ? 's' : '' }} maximum (hors bébés).
                            </p>

                            <button type="button" @click="editingGuests = false"
                                class="mt-3 text-sm font-semibold underline hover:text-gray-600 min-h-11 px-2 inline-flex items-center">
                                Fermer
                            </button>
                        </div>
                    </section>

                    <hr class="border-gray-200">

                    {{-- ─── SECTION 2 : Réductions long séjour ─── --}}
                    @if (count($longStayDiscounts) > 0)
                        <section>
                            <div class="flex items-center gap-2 mb-3">
                                <svg aria-hidden="true" class="w-5 h-5 text-green-600" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                                </svg>
                                <h2 class="text-lg font-semibold text-gray-900">Réductions long séjour</h2>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                @foreach ($longStayDiscounts as $discount)
                                    <span
                                        class="inline-flex items-center gap-1.5 bg-green-50 text-green-700 text-sm font-medium px-3 py-1.5 rounded-full border border-green-200">
                                        <span class="font-bold">-{{ $discount['percent'] }}%</span>
                                        <span class="text-green-600">{{ $discount['label'] }}</span>
                                    </span>
                                @endforeach
                            </div>
                        </section>
                        <hr class="border-gray-200">
                    @endif

                    {{-- ─── SECTION 3 : Code de réduction (pliable) ─── --}}
                    <section>
                        <button type="button" @click="showPromo = !showPromo"
                            class="flex items-center justify-between w-full text-left">
                            <h2 class="text-lg font-semibold text-gray-900">Code de réduction</h2>
                            <svg aria-hidden="true" class="w-5 h-5 text-gray-400 transition-transform duration-200"
                                :class="showPromo && 'rotate-180'" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>

                        <div x-show="showPromo" x-collapse x-cloak class="mt-4">
                            <div class="flex gap-2">
                                <input type="text" x-model="discountCode" placeholder="Entrez votre code"
                                    class="flex-1 text-sm rounded-lg border-gray-300 focus:border-gray-900 focus:ring-gray-900 min-h-11">
                                <button type="button" @click="applyCode()" :disabled="!discountCode"
                                    class="px-4 py-2.5 min-h-11 bg-gray-900 text-white text-sm font-medium rounded-lg hover:bg-gray-800 active:bg-gray-700 disabled:opacity-40 disabled:cursor-not-allowed transition-colors">
                                    Appliquer
                                </button>
                            </div>
                            <p x-show="codeError" x-text="codeError" class="mt-2 text-sm text-red-600" x-cloak></p>
                            <p x-show="codeSuccess" x-text="codeSuccess" class="mt-2 text-sm text-green-600" x-cloak></p>
                        </div>
                    </section>

                    <hr class="border-gray-200">

                    {{-- ─── SECTION 4 : Message au propriétaire ─── --}}
                    <section>
                        <h2 class="text-lg font-semibold text-gray-900 mb-4">
                            {{ $isInstant ? 'Envoyer un message au propriétaire' : 'Message au propriétaire' }}
                        </h2>

                        {{-- Mini profil hôte --}}
                        @if ($owner)
                            <div class="flex items-center gap-3 mb-4">
                                <img src="{{ $owner->getAvatarUrl() }}" alt="{{ $owner->name }}"
                                    class="w-12 h-12 rounded-full object-cover border-2 border-white shadow-sm">
                                <div>
                                    <p class="font-medium text-gray-900">{{ $owner->name }}</p>
                                    <p class="text-xs text-gray-500">
                                        Membre depuis {{ $owner->created_at->translatedFormat('F Y') }}
                                    </p>
                                </div>
                            </div>
                        @endif

                        @if (!$isInstant)
                            <p class="text-sm text-gray-500 mb-3">
                                Présentez-vous et dites au propriétaire pourquoi vous voyagez et quand vous arriverez.
                            </p>
                        @endif

                        <textarea x-model="message" rows="4" maxlength="1000"
                            placeholder="{{ $isInstant ? 'Dites bonjour au propriétaire…' : 'Bonjour, je souhaite réserver votre logement pour…' }}"
                            class="w-full text-sm rounded-xl border-gray-300 focus:border-gray-900 focus:ring-gray-900 resize-none"></textarea>
                        <p class="text-xs text-gray-400 mt-1 text-right" x-text="message.length + ' / 1 000'"></p>
                    </section>

                    <hr class="border-gray-200">

                    {{-- ─── SECTION 5 : Règles de base ─── --}}
                    <section>
                        <h2 class="text-lg font-semibold text-gray-900 mb-4">Règles de base</h2>
                        <div class="space-y-3">
                            <div class="flex items-center gap-3 text-sm">
                                <svg aria-hidden="true" class="w-5 h-5 text-gray-400 shrink-0" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span>Arrivée à partir de
                                    <strong>{{ $residence->check_in_time ?? '14:00' }}</strong></span>
                            </div>
                            <div class="flex items-center gap-3 text-sm">
                                <svg aria-hidden="true" class="w-5 h-5 text-gray-400 shrink-0" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span>Départ avant <strong>{{ $residence->check_out_time ?? '11:00' }}</strong></span>
                            </div>
                            <div class="flex items-center gap-3 text-sm">
                                <svg aria-hidden="true" class="w-5 h-5 text-gray-400 shrink-0" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                        d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                <span><strong>{{ $residence->max_guests }}</strong>
                                    voyageur{{ $residence->max_guests > 1 ? 's' : '' }} maximum</span>
                            </div>

                            @if (!empty($houseRules))
                                @foreach ($houseRules as $rule)
                                    <div class="flex items-center gap-3 text-sm">
                                        <svg aria-hidden="true" class="w-5 h-5 text-gray-400 shrink-0" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                                d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <span>{{ $rule }}</span>
                                    </div>
                                @endforeach
                            @endif
                        </div>
                    </section>

                    <hr class="border-gray-200">

                    {{-- ─── SECTION 6 : Politique d'annulation ─── --}}
                    <section>
                        <h2 class="text-lg font-semibold text-gray-900 mb-3">Politique d'annulation</h2>
                        @if ($policy)
                            <div class="bg-gray-50 rounded-xl p-4">
                                <p class="font-medium text-sm text-gray-900 mb-1">
                                    {{ $policy->display_name ?? $policy->name }}</p>
                                <p class="text-sm text-gray-600 leading-relaxed">{{ $policy->description }}</p>
                            </div>
                        @else
                            <div class="bg-gray-50 rounded-xl p-4">
                                <p class="font-medium text-sm text-gray-900 mb-1">Annulation flexible</p>
                                <p class="text-sm text-gray-600 leading-relaxed">Annulation gratuite jusqu'à 7 jours avant
                                    l'arrivée. Passé ce délai, les frais de la première nuit ne sont pas remboursés.</p>
                            </div>
                        @endif
                        <a href="{{ route('pages.cgu') }}" target="_blank"
                            class="inline-block mt-2 text-sm font-medium underline text-gray-700 hover:text-gray-900">
                            En savoir plus
                        </a>
                    </section>

                    <hr class="border-gray-200">

                    {{-- ─── SECTION 7 : Mode de paiement ─── --}}
                    <section>
                        <h2 class="text-lg font-semibold text-gray-900 mb-3">Mode de paiement</h2>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                            <template x-for="method in [
                                { id: 'wave', label: 'Wave', icon: '🌊', color: 'bg-blue-50 border-blue-300 text-blue-700' },
                                { id: 'orange', label: 'Orange Money', icon: '🟠', color: 'bg-[#fff0f3] border-[#ffb3c1] text-[#b5083a]' },
                                { id: 'mtn', label: 'MTN MoMo', icon: '🟡', color: 'bg-yellow-50 border-yellow-300 text-yellow-700' },
                                { id: 'moov', label: 'Moov Money', icon: '🔵', color: 'bg-cyan-50 border-cyan-300 text-cyan-700' },
                                { id: 'djamo', label: 'Djamo', icon: '💳', color: 'bg-purple-50 border-purple-300 text-purple-700' },
                            ]" :key="method.id">
                                <button type="button" @click="paymentMethod = method.id"
                                    :class="paymentMethod === method.id
                                        ? 'ring-2 ring-[#E21D63] border-[#E21D63] bg-pink-50'
                                        : 'border-gray-200 hover:border-gray-300 bg-white'"
                                    class="relative flex flex-col items-center gap-1.5 p-3 rounded-xl border-2 transition-all cursor-pointer">
                                    <span class="text-xl" x-text="method.icon"></span>
                                    <span class="text-xs font-medium text-gray-700" x-text="method.label"></span>
                                    <svg x-show="paymentMethod === method.id" x-cloak class="absolute top-1.5 right-1.5 w-4 h-4 text-[#E21D63]" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                </button>
                            </template>
                        </div>
                    </section>

                    {{-- ─── Sprint 3 — Paiement échelonné 50/50 ─── --}}
                    <section x-show="splitEligible" x-cloak class="rounded-2xl border-2 border-emerald-200 bg-emerald-50/60 p-4">
                        <label class="flex items-start gap-3 cursor-pointer">
                            <input type="checkbox" x-model="paymentSplit" name="payment_split" value="1"
                                class="mt-1 h-5 w-5 rounded border-emerald-300 text-emerald-600 focus:ring-emerald-500">
                            <div class="flex-1 min-w-0">
                                <div class="font-semibold text-emerald-900 text-sm flex items-center gap-2">
                                    💳 Payer en 2 fois (50% maintenant, 50% à J-30)
                                </div>
                                <div class="text-xs text-emerald-800 mt-1" x-show="!paymentSplit">
                                    Disponible car votre arrivée est dans plus de 30 jours.
                                </div>
                                <div class="text-xs text-emerald-900 mt-2 space-y-0.5" x-show="paymentSplit" x-cloak>
                                    <div>• Aujourd'hui : <strong x-text="formatCurrency(Math.round((price?.total_amount ?? 0) * 0.5))"></strong></div>
                                    <div>• Solde dû le <strong x-text="balanceDueLabel"></strong> : <strong x-text="formatCurrency(Math.round((price?.total_amount ?? 0) * 0.5))"></strong></div>
                                </div>
                            </div>
                        </label>
                    </section>

                    <hr class="border-gray-200">

                    {{-- ─── Erreur ─── --}}
                    <div x-show="bookingError" x-cloak class="p-4 rounded-xl bg-red-50 border border-red-200"
                        role="alert">
                        <div class="flex items-start gap-3">
                            <svg aria-hidden="true" class="w-5 h-5 text-red-500 shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                                    clip-rule="evenodd" />
                            </svg>
                            <p class="text-sm text-red-700" x-text="bookingError"></p>
                        </div>
                    </div>

                    {{-- ─── Erreurs de validation Laravel ─── --}}
                    @if ($errors->any())
                        <div class="p-4 rounded-xl bg-red-50 border border-red-200" role="alert">
                            <div class="flex items-start gap-3">
                                <svg aria-hidden="true" class="w-5 h-5 text-red-500 shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                                        clip-rule="evenodd" />
                                </svg>
                                <div>
                                    <p class="text-sm font-medium text-red-700 mb-1">Veuillez corriger les erreurs suivantes :</p>
                                    <ul class="list-disc list-inside text-sm text-red-600 space-y-0.5">
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- ─── SECTION 7 : Mentions légales + CTA ─── --}}
                    <section class="pb-8">
                        <p class="text-xs text-gray-500 mb-6 leading-relaxed">
                            En cliquant sur le bouton ci-dessous, j'accepte les
                            <a href="{{ route('pages.cgu') }}" target="_blank"
                                class="underline font-medium text-gray-700">conditions générales</a>,
                            la <a href="{{ route('pages.cgu') }}" target="_blank"
                                class="underline font-medium text-gray-700">politique d'annulation</a>
                            et le <a href="{{ route('pages.cgu') }}" target="_blank"
                                class="underline font-medium text-gray-700">règlement intérieur</a>
                            de ce logement.
                        </p>

                        {{-- CTA — Bouton principal --}}
                        <form
                            action="{{ $isInstant ? route('bookings.store.instant', $residence) : route('bookings.store.request', $residence) }}"
                            method="POST" @submit="submitting = true">
                            @csrf
                            <input type="hidden" name="check_in" :value="checkIn">
                            <input type="hidden" name="check_out" :value="checkOut">
                            <input type="hidden" name="guests" :value="adults + children">
                            <input type="hidden" name="adults" :value="adults">
                            <input type="hidden" name="children" :value="children">
                            <input type="hidden" name="infants" :value="infants">
                            <input type="hidden" name="message" :value="message">
                            <input type="hidden" name="promo_code"
                                :value="appliedCodeType === 'promo' ? appliedCode : ''">
                            <input type="hidden" name="coupon_code"
                                :value="appliedCodeType === 'coupon' ? appliedCode : ''">
                            <input type="hidden" name="payment_method" :value="paymentMethod">
                            <input type="hidden" name="payment_split" :value="paymentSplit && splitEligible ? 1 : 0">

                            <button type="submit" :disabled="!canSubmit || submitting"
                                class="w-full py-4 text-white text-base font-semibold rounded-xl transition-all
                                           bg-linear-to-r from-[#E21D63] to-[#D70466] hover:from-[#C9145A] hover:to-[#BD035D]
                                           disabled:opacity-50 disabled:cursor-not-allowed
                                           active:scale-[0.98] shadow-lg hover:shadow-xl">
                                <span x-show="!submitting" class="flex items-center justify-center gap-2">
                                    @if ($isInstant)
                                        <svg aria-hidden="true" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M13 10V3L4 14h7v7l9-11h-7z" />
                                        </svg>
                                        Confirmer et payer
                                    @else
                                        Demander une réservation
                                    @endif
                                </span>
                                <span x-show="submitting" x-cloak class="flex items-center justify-center gap-2">
                                    <svg aria-hidden="true" class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10"
                                            stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor"
                                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                        </path>
                                    </svg>
                                    Traitement en cours…
                                </span>
                            </button>
                        </form>

                        @if (!$isInstant)
                            <p class="text-center text-xs text-gray-500 mt-3 flex items-center justify-center gap-1.5">
                                <svg aria-hidden="true" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                Vous ne serez pas débité maintenant. Le propriétaire a 48h pour répondre.
                            </p>
                        @else
                            <p class="text-center text-xs text-gray-500 mt-3 flex items-center justify-center gap-1.5">
                                <svg aria-hidden="true" class="w-4 h-4 text-green-500" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                </svg>
                                Paiement sécurisé via Mobile Money ou carte bancaire
                            </p>
                        @endif
                    </section>
                </div>

                {{-- ═══ COLONNE DROITE — Carte résidence sticky ═══ --}}
                <div class="lg:w-95 shrink-0">
                    <div class="lg:sticky lg:top-21.25">
                        <div class="border border-gray-200 rounded-xl shadow-sm overflow-hidden">

                            {{-- Résidence photo + infos --}}
                            <div class="flex gap-3 sm:gap-4 p-4 sm:p-6 pb-4 sm:pb-5 border-b border-gray-200">
                                @if ($photo)
                                    <img src="{{ $photo->url }}" alt="{{ $residence->title }}"
                                        class="w-31 h-25 rounded-lg object-cover shrink-0">
                                @endif
                                <div class="flex-1 min-w-0">
                                    <p class="text-[10px] text-gray-500 uppercase tracking-wider font-medium">
                                        {{ $residence->category?->name ?? $residence->type }}</p>
                                    <h3 class="font-medium text-sm text-gray-900 mt-0.5 line-clamp-2">
                                        {{ $residence->title }}</h3>
                                    <p class="text-xs text-gray-500 mt-1">{{ $residence->commune ?? $residence->city }},
                                        {{ $residence->quartier ?? '' }}</p>

                                    @if ($residence->reviews_count > 0)
                                        <div class="flex items-center gap-1 mt-1.5 text-xs">
                                            <svg aria-hidden="true" class="w-3.5 h-3.5 text-gray-900" fill="currentColor"
                                                viewBox="0 0 20 20">
                                                <path
                                                    d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                            </svg>
                                            <span
                                                class="font-semibold">{{ number_format($residence->average_rating, 2) }}</span>
                                            <span class="text-gray-400">({{ $residence->reviews_count }})</span>
                                        </div>
                                    @endif

                                    @if ($residence->is_verified)
                                        <span
                                            class="inline-flex items-center gap-1 mt-1 text-[10px] text-green-700 bg-green-50 px-1.5 py-0.5 rounded font-medium">
                                            <svg aria-hidden="true" class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd"
                                                    d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                                    clip-rule="evenodd" />
                                            </svg>
                                            Vérifié
                                        </span>
                                    @endif
                                </div>
                            </div>

                            {{-- Détail du prix --}}
                            <div class="p-4 sm:p-6">
                                <h3 class="font-semibold text-gray-900 mb-4">Détail du prix</h3>

                                {{-- Chargement --}}
                                <div x-show="loading" class="flex items-center justify-center py-6">
                                    <svg aria-hidden="true" class="animate-spin w-6 h-6 text-gray-400" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10"
                                            stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor"
                                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                        </path>
                                    </svg>
                                </div>

                                {{-- Prix calculé --}}
                                <div x-show="!loading && price" x-cloak>
                                    <div class="space-y-3 text-sm">
                                        <div class="flex justify-between">
                                            <span class="underline decoration-dotted text-gray-600 cursor-help"
                                                x-text="formatCurrency(price?.avg_price_per_night) + ' × ' + price?.nights + ' jour' + (price?.nights > 1 ? 's' : '')"
                                                :title="'Prix moyen par jour'"></span>
                                            <span x-text="formatCurrency(price?.subtotal)"></span>
                                        </div>

                                        <div class="flex justify-between" x-show="price?.cleaning_fee > 0">
                                            <span class="underline decoration-dotted text-gray-600 cursor-help"
                                                title="Nettoyage professionnel du logement">Frais de ménage</span>
                                            <span x-text="formatCurrency(price?.cleaning_fee)"></span>
                                        </div>

                                        <div class="flex justify-between" x-show="price?.service_fee > 0">
                                            <span class="underline decoration-dotted text-gray-600 cursor-help"
                                                title="Support 24/7, garantie réservation, paiement sécurisé">Frais de
                                                service REZI</span>
                                            <span x-text="formatCurrency(price?.service_fee)"></span>
                                        </div>

                                        <div class="flex justify-between">
                                            <span class="underline decoration-dotted text-gray-600 cursor-help"
                                                title="Taxe d'État fixe">Taxe d'État</span>
                                            <span x-text="formatCurrency(price?.taxes)"></span>
                                        </div>

                                        <div class="flex justify-between text-green-600"
                                            x-show="price?.long_stay_discount > 0">
                                            <span>Réduction long séjour</span>
                                            <span x-text="'-' + formatCurrency(price?.long_stay_discount)"></span>
                                        </div>

                                        <div class="flex justify-between text-green-600"
                                            x-show="(price?.promo_discount > 0) || (price?.coupon_discount > 0)">
                                            <span>Code de réduction</span>
                                            <span
                                                x-text="'-' + formatCurrency((price?.promo_discount || 0) + (price?.coupon_discount || 0))"></span>
                                        </div>
                                    </div>

                                    {{-- Total --}}
                                    <div
                                        class="flex justify-between items-center pt-4 mt-4 border-t border-gray-200 font-semibold text-base">
                                        <span>Total <span class="text-xs font-normal text-gray-400">(XOF)</span></span>
                                        <span x-text="formatCurrency(price?.total_amount)"></span>
                                    </div>
                                </div>

                                {{-- Pas encore de prix --}}
                                <div x-show="!loading && !price" class="text-center py-6 text-gray-400 text-sm">
                                    <svg aria-hidden="true" class="w-10 h-10 mx-auto mb-2 text-gray-200" fill="none"
                                        stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    <p>Sélectionnez vos dates pour voir le prix</p>
                                </div>
                            </div>
                        </div>

                        {{-- Badge rareté --}}
                        @if ($residence->is_top_residence)
                            <div
                                class="mt-3 flex items-center gap-2 px-4 py-3 bg-rose-50 text-rose-700 rounded-xl text-sm border border-rose-100">
                                <svg aria-hidden="true" class="w-5 h-5 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M5 2a1 1 0 011 1v1h1a1 1 0 010 2H6v1a1 1 0 01-2 0V6H3a1 1 0 010-2h1V3a1 1 0 011-1zm0 10a1 1 0 011 1v1h1a1 1 0 110 2H6v1a1 1 0 11-2 0v-1H3a1 1 0 110-2h1v-1a1 1 0 011-1zM12 2a1 1 0 01.967.744l.311 1.242 1.105.553a1 1 0 010 1.789l-1.105.553-.31 1.242a1 1 0 01-1.934 0l-.311-1.242-1.105-.553a1 1 0 010-1.789l1.105-.553.311-1.242A1 1 0 0112 2z"
                                        clip-rule="evenodd" />
                                </svg>
                                <span class="font-medium">Ce logement est rarement disponible</span>
                            </div>
                        @endif

                        {{-- Info sécurité --}}
                        <div class="mt-3 flex items-start gap-2.5 px-4 py-3 text-xs text-gray-500">
                            <svg aria-hidden="true" class="w-4 h-4 text-green-500 shrink-0 mt-0.5" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                            </svg>
                            <p>Vos informations de paiement sont protégées par un chiffrement de bout en bout.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
