@extends('layouts.owner')

@section('title', 'Contacts d\'urgence - Rezi App')

@section('owner-content')
    <div class="space-y-6" x-data="{ showAddModal: false }">

        {{-- ============================== HEADER ============================== --}}
        <div>
            <a href="{{ route('verification.dashboard') }}"
                class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-900 transition-colors mb-3">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
                </svg>
                Centre de vérification
            </a>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-gray-900 rounded-xl flex items-center justify-center">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="2"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z" />
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-xl sm:text-2xl font-bold text-gray-900">Contacts d'urgence</h1>
                        <p class="text-sm text-gray-500 mt-0.5">{{ $contacts->count() }}/3 contacts enregistrés</p>
                    </div>
                </div>
                @if ($contacts->count() < 3)
                    <button type="button" @click="showAddModal = true"
                        class="inline-flex items-center gap-1.5 px-4 py-2 bg-gray-900 text-white text-sm font-semibold rounded-xl hover:bg-gray-800 transition-colors shadow-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                        </svg>
                        Ajouter
                    </button>
                @endif
            </div>
        </div>

        <div class="max-w-2xl space-y-5">

            {{-- ============================== FLASH MESSAGES ============================== --}}
            @if (session('success'))
                <div x-data="autoHide(4000)" x-show="show" x-transition
                    class="flex items-center gap-3 px-4 py-3 bg-emerald-50 border border-emerald-200 rounded-xl">
                    <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                            clip-rule="evenodd" />
                    </svg>
                    <p class="text-sm font-medium text-emerald-800">{{ session('success') }}</p>
                </div>
            @endif
            @if (session('error'))
                <div x-data="{ show: true }" x-show="show" x-transition
                    class="flex items-center gap-3 px-4 py-3 bg-red-50 border border-red-200 rounded-xl">
                    <svg class="w-5 h-5 text-red-600 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                            clip-rule="evenodd" />
                    </svg>
                    <p class="text-sm font-medium text-red-800">{{ session('error') }}</p>
                </div>
            @endif

            {{-- ============================== INFO ============================== --}}
            <div class="flex items-start gap-3 px-4 py-3 bg-blue-50 border border-blue-100 rounded-xl">
                <svg class="w-5 h-5 text-blue-600 shrink-0 mt-0.5" fill="none" stroke="currentColor"
                    stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
                </svg>
                <p class="text-sm text-blue-800">En cas d'alerte, vos contacts seront notifiés par SMS avec votre
                    localisation. Vous pouvez ajouter jusqu'à 3 contacts de confiance.</p>
            </div>

            {{-- ============================== LISTE DES CONTACTS ============================== --}}
            @if ($contacts->count() > 0)
                <div class="space-y-3">
                    @foreach ($contacts as $contact)
                        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                            <div class="p-5 sm:p-6">
                                <div class="flex items-start gap-4">
                                    <div
                                        class="shrink-0 w-11 h-11 rounded-xl flex items-center justify-center text-sm font-bold
                                    {{ $contact->is_primary ? 'bg-blue-50 text-blue-700' : 'bg-gray-100 text-gray-600' }}">
                                        {{ strtoupper(substr($contact->name, 0, 1)) }}
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-2 mb-0.5">
                                            <h3 class="text-sm font-semibold text-gray-900">{{ $contact->name }}</h3>
                                            @if ($contact->is_primary)
                                                <span
                                                    class="px-2 py-0.5 rounded-full text-[11px] font-bold bg-blue-50 text-blue-700">Principal</span>
                                            @endif
                                            @if ($contact->is_verified)
                                                <span
                                                    class="px-2 py-0.5 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-700">Vérifié</span>
                                            @endif
                                        </div>
                                        <p class="text-xs text-gray-500">{{ $contact->relationship }}</p>
                                        <div class="flex items-center gap-3 mt-1.5">
                                            <span class="text-xs text-gray-600">{{ $contact->phone }}</span>
                                            @if ($contact->email)
                                                <span class="text-gray-300">·</span>
                                                <span class="text-xs text-gray-500">{{ $contact->email }}</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-1 shrink-0">
                                        @if (!$contact->is_primary)
                                            <form action="{{ route('verification.emergency.set-primary', $contact) }}"
                                                method="POST" class="inline">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit"
                                                    class="p-2 text-gray-400 hover:text-blue-600 rounded-lg hover:bg-blue-50 transition-colors"
                                                    title="Définir comme principal"
                                                    aria-label="Définir comme contact principal">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                        stroke-width="2" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z" />
                                                    </svg>
                                                </button>
                                            </form>
                                        @endif

                                        <form action="{{ route('verification.emergency.destroy', $contact) }}"
                                            method="POST" class="inline"
                                             data-confirm='Supprimer ce contact d\'urgence ?'>
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                class="p-2 text-gray-400 hover:text-red-600 rounded-lg hover:bg-red-50 transition-colors"
                                                title="Supprimer" aria-label="Supprimer le contact">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                    stroke-width="2" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                                </svg>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                {{-- État vide --}}
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-8 sm:p-10 text-center">
                    <div class="w-14 h-14 rounded-2xl bg-gray-100 flex items-center justify-center mx-auto mb-4">
                        <svg class="w-7 h-7 text-gray-400" fill="none" stroke="currentColor" stroke-width="1.5"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M19 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zM4 19.235v-.11a6.375 6.375 0 0112.75 0v.109A12.318 12.318 0 0110.374 21c-2.331 0-4.512-.645-6.374-1.766z" />
                        </svg>
                    </div>
                    <h3 class="text-base font-bold text-gray-900 mb-1">Aucun contact d'urgence</h3>
                    <p class="text-sm text-gray-500 mb-5 max-w-sm mx-auto">Ajoutez des personnes de confiance qui seront
                        alertées en cas d'urgence.</p>
                    <button type="button" @click="showAddModal = true"
                        class="inline-flex items-center gap-2 px-5 py-2.5 bg-gray-900 text-white text-sm font-semibold rounded-xl hover:bg-gray-800 transition-colors shadow-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                        </svg>
                        Ajouter un contact
                    </button>
                </div>
            @endif

            {{-- ============================== CONSEILS ============================== --}}
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="p-5 sm:p-6">
                    <h3 class="text-sm font-semibold text-gray-900 mb-3">Conseils de sécurité</h3>
                    <div class="space-y-2.5">
                        <div class="flex items-start gap-2.5">
                            <div
                                class="shrink-0 w-6 h-6 rounded-md bg-emerald-50 flex items-center justify-center mt-0.5">
                                <svg class="w-3.5 h-3.5 text-emerald-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                        clip-rule="evenodd" />
                                </svg>
                            </div>
                            <p class="text-xs text-gray-600">Choisissez des personnes joignables rapidement</p>
                        </div>
                        <div class="flex items-start gap-2.5">
                            <div
                                class="shrink-0 w-6 h-6 rounded-md bg-emerald-50 flex items-center justify-center mt-0.5">
                                <svg class="w-3.5 h-3.5 text-emerald-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                        clip-rule="evenodd" />
                                </svg>
                            </div>
                            <p class="text-xs text-gray-600">Informez-les qu'ils peuvent recevoir des alertes de Rezi App</p>
                        </div>
                        <div class="flex items-start gap-2.5">
                            <div
                                class="shrink-0 w-6 h-6 rounded-md bg-emerald-50 flex items-center justify-center mt-0.5">
                                <svg class="w-3.5 h-3.5 text-emerald-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                        clip-rule="evenodd" />
                                </svg>
                            </div>
                            <p class="text-xs text-gray-600">Le contact principal sera alerté en premier</p>
                        </div>
                        <div class="flex items-start gap-2.5">
                            <div
                                class="shrink-0 w-6 h-6 rounded-md bg-emerald-50 flex items-center justify-center mt-0.5">
                                <svg class="w-3.5 h-3.5 text-emerald-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                        clip-rule="evenodd" />
                                </svg>
                            </div>
                            <p class="text-xs text-gray-600">Déclenchez une alerte depuis le centre de vérification</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ============================== MODAL AJOUT ============================== --}}
        <div x-show="showAddModal" x-cloak class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-4">
            <div x-show="showAddModal" x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-150"
                x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                class="fixed inset-0 bg-black/50" @click="showAddModal = false"></div>

            <div x-show="showAddModal" x-transition:enter="ease-out duration-200"
                x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-150"
                x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                class="relative bg-white rounded-t-2xl sm:rounded-2xl shadow-2xl max-w-md w-full overflow-hidden">

                {{-- Modal Header --}}
                <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
                    <h3 class="text-base font-bold text-gray-900">Ajouter un contact</h3>
                    <button @click="showAddModal = false"
                        class="p-1.5 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100 transition-colors"
                        aria-label="Fermer">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                {{-- Modal Body --}}
                <form action="{{ route('verification.emergency.store') }}" method="POST">
                    @csrf
                    <div class="px-5 py-5 space-y-4">
                        <div>
                            <label for="name" class="block text-sm font-semibold text-gray-900 mb-1.5">Nom
                                complet</label>
                            <input type="text" name="name" id="name" required
                                class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 bg-gray-50 focus:bg-white focus:border-gray-900 focus:ring-1 focus:ring-gray-900 text-sm transition-all outline-none"
                                placeholder="Ex: Jean Konan">
                        </div>

                        <div>
                            <label for="relationship"
                                class="block text-sm font-semibold text-gray-900 mb-1.5">Relation</label>
                            <select name="relationship" id="relationship" required
                                class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 bg-gray-50 focus:bg-white focus:border-gray-900 focus:ring-1 focus:ring-gray-900 text-sm transition-all outline-none">
                                <option value="">Sélectionnez…</option>
                                <option value="Conjoint(e)">Conjoint(e)</option>
                                <option value="Parent">Parent</option>
                                <option value="Frère/Sœur">Frère/Sœur</option>
                                <option value="Enfant">Enfant</option>
                                <option value="Ami(e)">Ami(e)</option>
                                <option value="Collègue">Collègue</option>
                                <option value="Autre">Autre</option>
                            </select>
                        </div>

                        <div>
                            <label for="phone"
                                class="block text-sm font-semibold text-gray-900 mb-1.5">Téléphone</label>
                            <input type="tel" name="phone" id="phone" required
                                class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 bg-gray-50 focus:bg-white focus:border-gray-900 focus:ring-1 focus:ring-gray-900 text-sm transition-all outline-none"
                                placeholder="+225 XX XX XX XX XX">
                        </div>

                        <div>
                            <label for="email" class="block text-sm font-semibold text-gray-900 mb-1.5">Email <span
                                    class="text-gray-400 font-normal">(optionnel)</span></label>
                            <input type="email" name="email" id="email"
                                class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 bg-gray-50 focus:bg-white focus:border-gray-900 focus:ring-1 focus:ring-gray-900 text-sm transition-all outline-none"
                                placeholder="contact@example.com">
                        </div>

                        <div class="flex items-center gap-2.5">
                            <input type="checkbox" name="is_primary" id="is_primary" value="1"
                                class="w-4 h-4 rounded border-gray-300 text-gray-900 focus:ring-gray-900">
                            <label for="is_primary" class="text-sm text-gray-700">Définir comme contact principal</label>
                        </div>
                    </div>

                    {{-- Modal Footer --}}
                    <div class="flex items-center justify-end gap-3 px-5 py-4 border-t border-gray-100 bg-gray-50/50">
                        <button type="button" @click="showAddModal = false"
                            class="px-4 py-2 text-sm font-semibold text-gray-700 bg-gray-100 rounded-xl hover:bg-gray-200 transition-colors">
                            Annuler
                        </button>
                        <button type="submit"
                            class="px-5 py-2 bg-gray-900 text-white text-sm font-semibold rounded-xl hover:bg-gray-800 transition-colors shadow-sm">
                            Ajouter le contact
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
