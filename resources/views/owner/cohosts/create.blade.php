@extends('layouts.owner')

@section('title', 'Inviter un co-hôte - ' . $residence->name . ' - Rezi App')

@section('owner-content')
    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {{-- Retour --}}
        <a href="{{ route('owner.cohosts.index', $residence) }}"
            class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-[#F16A00] transition mb-6">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
            Retour aux co-hôtes
        </a>

        <h1 class="text-2xl font-extrabold text-gray-900">Inviter un co-hôte</h1>
        <p class="mt-1 text-sm text-gray-500 mb-8">{{ $residence->name }}</p>

        {{-- Formulaire --}}
        <form action="{{ route('owner.cohosts.store', $residence) }}" method="POST" class="space-y-6">
            @csrf

            {{-- Informations --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h2 class="text-base font-bold text-gray-900 mb-4">Informations du co-hôte</h2>
                <div class="space-y-5">
                    <div>
                        <label for="name" class="block text-sm font-semibold text-gray-700 mb-2">Nom complet <span class="text-red-500">*</span></label>
                        <input type="text" name="name" id="name" value="{{ old('name') }}" required
                            placeholder="Jean Dupont"
                            class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[#F16A00] focus:border-[#F16A00] transition">
                        @error('name')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="email" class="block text-sm font-semibold text-gray-700 mb-2">Adresse email <span class="text-red-500">*</span></label>
                        <input type="email" name="email" id="email" value="{{ old('email') }}" required
                            placeholder="jean@exemple.com"
                            class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[#F16A00] focus:border-[#F16A00] transition">
                        <p class="mt-1 text-[11px] text-gray-400">Une invitation sera envoyée à cette adresse</p>
                        @error('email')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="phone" class="block text-sm font-semibold text-gray-700 mb-2">Téléphone</label>
                        <input type="tel" name="phone" id="phone" value="{{ old('phone') }}"
                            placeholder="+225 07 00 00 00 00"
                            class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[#F16A00] focus:border-[#F16A00] transition">
                    </div>
                </div>
            </div>

            {{-- Permissions --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h2 class="text-base font-bold text-gray-900 mb-1">Permissions</h2>
                <p class="text-xs text-gray-500 mb-5">Choisissez ce que votre co-hôte peut faire</p>

                <div class="space-y-3">
                    @php
                        $perms = [
                            'can_respond_messages' => ['label' => 'Répondre aux messages', 'desc' => 'Communiquer avec les clients intéressés', 'bg' => 'bg-amber-50', 'text' => 'text-amber-600', 'icon' => 'M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z', 'checked' => true],
                            'can_manage_calendar' => ['label' => 'Gérer le calendrier', 'desc' => 'Bloquer ou libérer des dates', 'bg' => 'bg-purple-50', 'text' => 'text-purple-600', 'icon' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z', 'checked' => true],
                            'can_accept_bookings' => ['label' => 'Accepter les réservations', 'desc' => 'Confirmer ou refuser les demandes', 'bg' => 'bg-pink-50', 'text' => 'text-pink-600', 'icon' => 'M5 13l4 4L19 7', 'checked' => false],
                            'can_manage_pricing' => ['label' => 'Modifier les prix', 'desc' => 'Ajuster les tarifs et promotions', 'bg' => 'bg-[#FFF4EB]', 'text' => 'text-[#CC5A00]', 'icon' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z', 'checked' => false],
                            'can_edit_listing' => ['label' => 'Modifier l\'annonce', 'desc' => 'Changer la description, photos, équipements', 'bg' => 'bg-blue-50', 'text' => 'text-blue-600', 'icon' => 'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z', 'checked' => false],
                            'can_view_earnings' => ['label' => 'Voir les revenus', 'desc' => 'Accéder aux statistiques financières', 'bg' => 'bg-indigo-50', 'text' => 'text-indigo-600', 'icon' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z', 'checked' => false],
                        ];
                    @endphp
                    @foreach ($perms as $key => $perm)
                        <label class="flex items-start gap-4 p-4 border border-gray-200 rounded-xl cursor-pointer hover:bg-gray-50 has-checked:border-[#F16A00] has-checked:bg-[#FFF4EB]/50 transition">
                            <input type="checkbox" name="{{ $key }}" value="1" {{ $perm['checked'] ? 'checked' : '' }}
                                class="mt-1 w-4 h-4 text-[#F16A00] border-gray-300 rounded focus:ring-[#F16A00]">
                            <div class="flex items-start gap-3 flex-1">
                                <div class="w-9 h-9 rounded-lg {{ $perm['bg'] }} flex items-center justify-center shrink-0 mt-0.5">
                                    <svg class="w-4 h-4 {{ $perm['text'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $perm['icon'] }}" />
                                    </svg>
                                </div>
                                <div>
                                    <span class="font-semibold text-sm text-gray-900">{{ $perm['label'] }}</span>
                                    <p class="text-xs text-gray-500 mt-0.5">{{ $perm['desc'] }}</p>
                                </div>
                            </div>
                        </label>
                    @endforeach
                </div>
            </div>

            {{-- Commission --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h2 class="text-base font-bold text-gray-900 mb-1">Commission (optionnel)</h2>
                <p class="text-xs text-gray-500 mb-4">Si vous rémunérez votre co-hôte sur les réservations</p>

                <div class="flex items-center gap-3">
                    <input type="number" name="commission_percent" id="commission_percent"
                        value="{{ old('commission_percent') }}" min="0" max="100" step="0.5" placeholder="0"
                        class="w-24 px-4 py-2.5 border border-gray-200 rounded-xl text-sm text-center focus:ring-2 focus:ring-[#F16A00] focus:border-[#F16A00] transition">
                    <span class="text-sm text-gray-500">% des réservations</span>
                </div>
            </div>

            {{-- Notes --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <label for="notes" class="block text-base font-bold text-gray-900 mb-2">Notes internes</label>
                <textarea name="notes" id="notes" rows="3"
                    placeholder="Notes visibles uniquement par vous (ex : relation, consignes spéciales...)"
                    class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[#F16A00] focus:border-[#F16A00] transition resize-y">{{ old('notes') }}</textarea>
            </div>

            {{-- Actions --}}
            <div class="flex gap-3">
                <a href="{{ route('owner.cohosts.index', $residence) }}"
                    class="flex-1 px-6 py-3 bg-gray-100 text-gray-700 text-sm font-semibold text-center rounded-xl hover:bg-gray-200 transition">
                    Annuler
                </a>
                <button type="submit"
                    class="flex-1 px-6 py-3 bg-[#F16A00] text-white text-sm font-semibold rounded-xl hover:bg-[#CC5A00] transition shadow-sm">
                    Envoyer l'invitation
                </button>
            </div>
        </form>
    </div>
@endsection
