@extends('layouts.owner')

@section('title', 'Nouvelle réponse automatique - Rezi Studio Meublé Faya')

@section('owner-content')
    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {{-- Retour --}}
        <a href="{{ route('owner.auto-replies.index') }}"
            class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-[#F16A00] transition mb-6">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
            Retour aux réponses
        </a>

        <h1 class="text-2xl font-extrabold text-gray-900 mb-8">Nouvelle réponse automatique</h1>

        <form action="{{ route('owner.auto-replies.store') }}" method="POST" class="space-y-6"
            x-data="autoReplyForm({{ \Illuminate\Support\Js::encode(['triggerType' => old('trigger_type', request('template', 'manual')), 'keywords' => old('trigger_conditions.keywords', [])]) }})">
            @csrf

            {{-- Nom & Résidence --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <div class="space-y-5">
                    <div>
                        <label for="name" class="block text-sm font-semibold text-gray-700 mb-2">Nom de la réponse <span class="text-red-500">*</span></label>
                        <input type="text" name="name" id="name" value="{{ old('name') }}" required
                            placeholder="Ex : Message de bienvenue"
                            class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[#F16A00] focus:border-[#F16A00] transition">
                        @error('name')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="residence_id" class="block text-sm font-semibold text-gray-700 mb-2">Résidence concernée</label>
                        <select name="residence_id" id="residence_id"
                            class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[#F16A00] focus:border-[#F16A00] transition">
                            <option value="">Toutes mes résidences</option>
                            @foreach ($residences as $residence)
                                <option value="{{ $residence->id }}" {{ old('residence_id') == $residence->id ? 'selected' : '' }}>
                                    {{ $residence->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            {{-- Type de déclencheur --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h2 class="text-base font-bold text-gray-900 mb-4">Quand envoyer cette réponse ?</h2>

                <div class="space-y-3">
                    @php
                        $triggerTypes = [
                            'first_contact' => ['label' => 'Premier contact', 'desc' => 'Automatiquement envoyé quand un client vous contacte pour la première fois', 'bg' => 'bg-blue-50', 'text' => 'text-blue-600', 'icon' => 'M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z'],
                            'keywords' => ['label' => 'Mots-clés détectés', 'desc' => 'Déclenché quand le message contient certains mots', 'bg' => 'bg-purple-50', 'text' => 'text-purple-600', 'icon' => 'M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z'],
                            'schedule' => ['label' => 'Horaire programmé', 'desc' => 'Envoyé automatiquement pendant certaines heures (ex : la nuit)', 'bg' => 'bg-amber-50', 'text' => 'text-amber-600', 'icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'],
                            'manual' => ['label' => 'Réponse rapide', 'desc' => 'Utilisable en un clic lors de la rédaction d\'un message', 'bg' => 'bg-gray-50', 'text' => 'text-gray-600', 'icon' => 'M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z'],
                        ];
                    @endphp
                    @foreach ($triggerTypes as $key => $type)
                        <label class="flex items-start gap-4 p-4 border-2 rounded-xl cursor-pointer transition"
                            :class="triggerType === '{{ $key }}' ? 'border-[#F16A00] bg-[#FFF4EB]/50' : 'border-gray-200 hover:border-gray-300'">
                            <input type="radio" name="trigger_type" value="{{ $key }}" x-model="triggerType" class="mt-1 w-4 h-4 text-[#F16A00] border-gray-300 focus:ring-[#F16A00]">
                            <div class="flex items-start gap-3 flex-1">
                                <div class="w-9 h-9 rounded-lg {{ $type['bg'] }} flex items-center justify-center shrink-0 mt-0.5">
                                    <svg class="w-4 h-4 {{ $type['text'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $type['icon'] }}" />
                                    </svg>
                                </div>
                                <div>
                                    <span class="font-semibold text-sm text-gray-900">{{ $type['label'] }}</span>
                                    <p class="text-xs text-gray-500 mt-0.5">{{ $type['desc'] }}</p>
                                </div>
                            </div>
                        </label>
                    @endforeach
                </div>
            </div>

            {{-- Configuration mots-clés --}}
            <div x-show="triggerType === 'keywords'" x-transition class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h2 class="text-base font-bold text-gray-900 mb-2">Mots-clés déclencheurs</h2>
                <p class="text-xs text-gray-500 mb-4">Le message sera envoyé si le client utilise un de ces mots</p>

                <div class="flex flex-wrap gap-2 mb-3" x-show="keywords.length > 0">
                    <template x-for="(keyword, index) in keywords" :key="index">
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-purple-50 text-purple-700 rounded-full text-xs font-medium">
                            <span x-text="keyword"></span>
                            <button type="button" @click="keywords.splice(index, 1)" class="hover:text-purple-900 transition">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                            <input type="hidden" name="trigger_conditions[keywords][]" :value="keyword">
                        </span>
                    </template>
                </div>

                <div class="flex gap-2">
                    <input type="text" x-model="newKeyword" @keydown.enter.prevent="addKeyword()"
                        placeholder="Tapez un mot-clé et appuyez Entrée"
                        class="flex-1 px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[#F16A00] focus:border-[#F16A00]">
                    <button type="button" @click="addKeyword()"
                        class="px-4 py-2.5 bg-purple-600 text-white text-sm font-semibold rounded-xl hover:bg-purple-700 transition">
                        Ajouter
                    </button>
                </div>
                <p class="text-[11px] text-gray-400 mt-2">Exemples : prix, disponible, check-in, wifi, parking</p>
            </div>

            {{-- Configuration horaires --}}
            <div x-show="triggerType === 'schedule'" x-transition class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h2 class="text-base font-bold text-gray-900 mb-4">Horaires d'envoi automatique</h2>

                <div class="grid grid-cols-2 gap-4 mb-5">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">De</label>
                        <input type="time" name="trigger_conditions[start_time]"
                            value="{{ old('trigger_conditions.start_time', '22:00') }}"
                            class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[#F16A00] focus:border-[#F16A00]">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">À</label>
                        <input type="time" name="trigger_conditions[end_time]"
                            value="{{ old('trigger_conditions.end_time', '08:00') }}"
                            class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[#F16A00] focus:border-[#F16A00]">
                    </div>
                </div>

                <p class="text-sm font-semibold text-gray-700 mb-2">Jours actifs :</p>
                <div class="flex flex-wrap gap-2">
                    @foreach (['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'] as $index => $day)
                        <label class="flex items-center gap-2 px-3 py-2 border border-gray-200 rounded-lg cursor-pointer text-sm hover:bg-gray-50 has-checked:bg-[#FFF4EB] has-checked:border-[#F16A00] transition">
                            <input type="checkbox" name="trigger_conditions[days][]" value="{{ $index }}" checked
                                class="w-4 h-4 text-[#F16A00] border-gray-300 rounded focus:ring-[#F16A00]">
                            {{ $day }}
                        </label>
                    @endforeach
                </div>
            </div>

            {{-- Message --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h2 class="text-base font-bold text-gray-900 mb-4">Message</h2>

                <textarea name="message" rows="6" required placeholder="Écrivez votre message automatique ici..."
                    class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[#F16A00] focus:border-[#F16A00] transition resize-y">{{ old('message') }}</textarea>

                {{-- Variables disponibles --}}
                <div class="mt-4 p-4 bg-gray-50 rounded-xl">
                    <p class="text-xs font-semibold text-gray-700 mb-2">Variables disponibles :</p>
                    <div class="flex flex-wrap gap-2">
                        @foreach (['{guest_name}' => 'Nom du client', '{residence_name}' => 'Nom de la résidence', '{owner_name}' => 'Votre nom', '{checkin_time}' => 'Heure check-in', '{checkout_time}' => 'Heure check-out'] as $var => $desc)
                            <button type="button" @click="insertVariable('{{ $var }}')"
                                class="inline-flex items-center gap-1.5 px-2.5 py-1.5 bg-white border border-gray-200 rounded-lg text-[11px] hover:border-[#FF8A1F] hover:bg-[#FFF4EB] transition">
                                <code class="text-[#F16A00] font-semibold">{{ $var }}</code>
                                <span class="text-gray-400">{{ $desc }}</span>
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Délai --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-base font-bold text-gray-900">Délai avant envoi</h2>
                        <p class="text-xs text-gray-500 mt-0.5">Pour paraître plus naturel</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <input type="number" name="delay_minutes" value="{{ old('delay_minutes', 0) }}" min="0" max="60"
                            class="w-20 px-3 py-2 border border-gray-200 rounded-xl text-sm text-center focus:ring-2 focus:ring-[#F16A00] focus:border-[#F16A00]">
                        <span class="text-sm text-gray-500">min</span>
                    </div>
                </div>
            </div>

            {{-- Actions --}}
            <div class="flex gap-3">
                <a href="{{ route('owner.auto-replies.index') }}"
                    class="flex-1 px-6 py-3 bg-gray-100 text-gray-700 text-sm font-semibold text-center rounded-xl hover:bg-gray-200 transition">
                    Annuler
                </a>
                <button type="submit"
                    class="flex-1 px-6 py-3 bg-[#F16A00] text-white text-sm font-semibold rounded-xl hover:bg-[#CC5A00] transition shadow-sm">
                    Créer la réponse
                </button>
            </div>
        </form>
    </div>
@endsection
