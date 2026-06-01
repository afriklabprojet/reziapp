@extends('layouts.owner')

@section('title', 'Calendrier - ' . $residence->name)

@section('owner-content')
    <div class="max-w-7xl mx-auto space-y-6">
        {{-- En-tête --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <a href="{{ route('owner.bookings.index') }}"
                    class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-[#F16A00] transition mb-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                    Retour aux réservations
                </a>
                <h1 class="text-xl font-extrabold text-gray-900 flex items-center gap-2">
                    <div class="w-8 h-8 bg-[#FFE7D1] rounded-lg flex items-center justify-center">
                        <svg class="w-4 h-4 text-[#F16A00]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                    Calendrier de disponibilité
                </h1>
                <p class="text-sm text-gray-500 mt-1">{{ $residence->name }}</p>
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route('owner.pricing.index', $residence) }}"
                    class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-semibold text-gray-700 bg-white border border-gray-200 rounded-xl hover:bg-gray-50 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Gérer les prix
                </a>
                <button type="button"
                    onclick="document.getElementById('blockDatesModal').classList.remove('hidden'); document.getElementById('blockDatesModal').style.display = 'flex';"
                    class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-semibold text-white bg-[#F16A00] rounded-xl hover:bg-[#CC5A00] transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                    </svg>
                    Bloquer des dates
                </button>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
            {{-- Calendrier principal --}}
            <div class="lg:col-span-3 space-y-4" x-data="bookingCalendar(@js(['calendar' => $calendar]))">
                {{-- Navigation du mois --}}
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <div class="flex items-center justify-between mb-6">
                        <button @click="previousMonth()"
                            class="w-10 h-10 flex items-center justify-center rounded-xl hover:bg-gray-100 transition"
                            aria-label="Mois précédent">
                            <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                            </svg>
                        </button>
                        <h2 x-text="currentMonthName" class="text-sm font-bold text-gray-900"></h2>
                        <button @click="nextMonth()"
                            class="w-10 h-10 flex items-center justify-center rounded-xl hover:bg-gray-100 transition"
                            aria-label="Mois suivant">
                            <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </button>
                    </div>

                    {{-- Jours de la semaine --}}
                    <div class="grid grid-cols-7 gap-2 mb-3">
                        @foreach (['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'] as $dayName)
                            <div class="text-center text-[11px] font-semibold text-gray-400 uppercase tracking-wider py-2">
                                {{ $dayName }}</div>
                        @endforeach
                    </div>

                    {{-- Grille du calendrier --}}
                    <div class="grid grid-cols-7 gap-2">
                        <template x-for="day in calendarDays" :key="day.date || Math.random()">
                            <div :class="{
                                'bg-gray-50 text-gray-300': day.isEmpty,
                                'bg-green-50 hover:bg-green-100 cursor-pointer border border-green-200/50': day
                                    .available && !day.isEmpty,
                                'bg-red-50 border border-red-200/50': day.hasBooking && !day.isEmpty,
                                'bg-gray-100 border border-gray-200/50': day.isBlocked && !day.isEmpty,
                                'ring-2 ring-[#F16A00] ring-offset-1': day.isSelected,
                            }"
                                @click="day.available && selectDate(day)"
                                class="aspect-square rounded-xl p-2 relative transition-all">
                                <span x-text="day.dayOfMonth" class="text-sm font-semibold"></span>

                                {{-- Indicateur de réservation --}}
                                <div x-show="day.hasBooking" class="absolute bottom-1.5 left-1.5 right-1.5">
                                    <div class="h-1 bg-red-400 rounded-full"></div>
                                </div>

                                {{-- Prix spécial --}}
                                <div x-show="day.price && !day.isEmpty"
                                    class="absolute bottom-1.5 right-1.5 text-[10px] text-gray-500 font-medium">
                                    <span x-text="day.price ? (day.price / 1000) + 'k' : ''"></span>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                {{-- Légende --}}
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4">
                    <div class="flex flex-wrap items-center gap-5 text-sm">
                        <div class="flex items-center gap-2">
                            <span class="w-4 h-4 bg-green-50 border border-green-200 rounded-lg"></span>
                            <span class="text-xs text-gray-600">Disponible</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="w-4 h-4 bg-red-50 border border-red-200 rounded-lg"></span>
                            <span class="text-xs text-gray-600">Réservé</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="w-4 h-4 bg-gray-100 border border-gray-200 rounded-lg"></span>
                            <span class="text-xs text-gray-600">Bloqué</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="w-4 h-4 rounded-lg ring-2 ring-[#F16A00] ring-offset-1"></span>
                            <span class="text-xs text-gray-600">Sélectionné</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Sidebar --}}
            <div class="lg:col-span-1 space-y-4">
                {{-- Stats rapides du mois --}}
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                    <h3 class="text-xs font-bold text-gray-900 uppercase tracking-wider mb-4">Ce mois</h3>
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-500">Nuits réservées</span>
                            <span class="text-sm font-bold text-gray-900">{{ $bookings->sum('nights') }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-500">Revenus prévus</span>
                            <span
                                class="text-sm font-bold text-green-600">{{ number_format($bookings->sum('total_amount'), 0, ',', ' ') }}
                                F</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-500">Occupation</span>
                            <div class="flex items-center gap-2">
                                <div class="w-16 h-1.5 bg-gray-100 rounded-full overflow-hidden">
                                    <div class="h-full bg-[#F16A00] rounded-full"
                                        style="width: {{ min(round(($bookings->sum('nights') / 30) * 100), 100) }}%"></div>
                                </div>
                                <span
                                    class="text-sm font-bold text-gray-900">{{ min(round(($bookings->sum('nights') / 30) * 100), 100) }}%</span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Réservations à venir --}}
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="p-5 border-b border-gray-100">
                        <h3 class="text-xs font-bold text-gray-900 uppercase tracking-wider">Réservations à venir</h3>
                        <p class="text-[11px] text-gray-400 mt-0.5">{{ $bookings->count() }}
                            réservation{{ $bookings->count() > 1 ? 's' : '' }}</p>
                    </div>

                    @forelse($bookings as $booking)
                        <div class="p-4 border-b border-gray-100 last:border-0 hover:bg-gray-50/50 transition">
                            <div class="flex items-center gap-3 mb-2">
                                @if ($booking->user->avatar)
                                    <img loading="lazy" src="{{ $booking->user->avatar }}"
                                        alt="{{ $booking->user->name }}" class="w-8 h-8 rounded-full object-cover">
                                @else
                                    @php $colors = ['from-[#FF8A1F] to-pink-500', 'from-blue-400 to-purple-500', 'from-green-400 to-cyan-500', 'from-amber-400 to-red-500']; @endphp
                                    <div
                                        class="w-8 h-8 rounded-full bg-linear-to-br {{ $colors[$booking->user->id % 4] }} flex items-center justify-center">
                                        <span
                                            class="text-xs font-bold text-white">{{ mb_substr($booking->user->first_name ?? $booking->user->name, 0, 1) }}</span>
                                    </div>
                                @endif
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-semibold text-gray-900 truncate">
                                        {{ $booking->user->first_name ?? $booking->user->name }}</p>
                                    <p class="text-[11px] text-gray-400">{{ $booking->reference }}</p>
                                </div>
                            </div>

                            <div class="flex items-center justify-between text-sm">
                                <span class="text-gray-500">
                                    {{ \Carbon\Carbon::parse($booking->check_in)->locale('fr')->isoFormat('D MMM') }}
                                    →
                                    {{ \Carbon\Carbon::parse($booking->check_out)->locale('fr')->isoFormat('D MMM') }}
                                </span>
                            </div>

                            <div class="flex items-center justify-between mt-2">
                                <span
                                    class="text-sm font-bold text-[#CC5A00]">{{ number_format($booking->total_amount, 0, ',', ' ') }}
                                    F</span>
                                @if ($booking->status === 'pending')
                                    <span
                                        class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-700">En
                                        attente</span>
                                @elseif($booking->status === 'confirmed')
                                    <span
                                        class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-green-100 text-green-700">Confirmée</span>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="p-6 text-center">
                            <div class="w-10 h-10 bg-gray-100 rounded-xl flex items-center justify-center mx-auto mb-2">
                                <svg class="w-5 h-5 text-gray-300" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <p class="text-sm text-gray-500">Aucune réservation à venir</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    {{-- Modal bloquer dates --}}
    <div id="blockDatesModal" class="fixed inset-0 bg-black/50 z-50 hidden items-center justify-center p-4"
        role="dialog" aria-modal="true" aria-label="Bloquer des dates" style="display: none;">
        <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-xl">
            <div class="flex items-center justify-between mb-5">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 bg-red-100 rounded-lg flex items-center justify-center">
                        <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                        </svg>
                    </div>
                    <h3 class="text-sm font-bold text-gray-900">Bloquer des dates</h3>
                </div>
                <button type="button"
                    onclick="document.getElementById('blockDatesModal').classList.add('hidden'); document.getElementById('blockDatesModal').style.display = 'none';"
                    class="w-8 h-8 flex items-center justify-center rounded-lg hover:bg-gray-100 transition">
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form action="{{ route('owner.residences.block-dates', $residence) }}" method="POST">
                @csrf
                <div class="space-y-4">
                    <div>
                        <label class="block text-[11px] font-semibold text-gray-500 uppercase tracking-wider mb-1">Date de
                            début</label>
                        <input type="date" name="start_date" required min="{{ now()->format('Y-m-d') }}"
                            class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[#F16A00]/20 focus:border-[#F16A00] transition">
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-gray-500 uppercase tracking-wider mb-1">Date de
                            fin</label>
                        <input type="date" name="end_date" required
                            class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[#F16A00]/20 focus:border-[#F16A00] transition">
                    </div>
                    <div>
                        <label
                            class="block text-[11px] font-semibold text-gray-500 uppercase tracking-wider mb-1">Raison</label>
                        <select name="reason"
                            class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[#F16A00]/20 focus:border-[#F16A00] transition">
                            <option value="personal">Usage personnel</option>
                            <option value="maintenance">Maintenance</option>
                            <option value="renovation">Rénovation</option>
                            <option value="booking">Réservation externe</option>
                            <option value="other">Autre</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-gray-500 uppercase tracking-wider mb-1">Notes
                            (optionnel)</label>
                        <textarea name="notes" rows="2"
                            class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[#F16A00]/20 focus:border-[#F16A00] transition resize-none"
                            placeholder="Ajoutez une note..."></textarea>
                    </div>
                </div>

                <div class="flex items-center gap-3 mt-6">
                    <button type="button"
                        onclick="document.getElementById('blockDatesModal').classList.add('hidden'); document.getElementById('blockDatesModal').style.display = 'none';"
                        class="flex-1 px-4 py-2.5 text-sm font-semibold text-gray-700 bg-gray-100 rounded-xl hover:bg-gray-200 transition">
                        Annuler
                    </button>
                    <button type="submit"
                        class="flex-1 px-4 py-2.5 text-sm font-semibold text-white bg-[#F16A00] rounded-xl hover:bg-[#CC5A00] transition">
                        Bloquer
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
