{{--
    Chatbot IA REZI — widget flottant 24/7
    Usage : <x-chatbot />
    Params optionnels : commune, budget, residence
--}}
@props([
    'commune'   => null,
    'budget'    => null,
    'residence' => null,
])

@php
$config = json_encode([
    'commune'   => $commune,
    'budget'    => $budget,
    'residence' => $residence,
]);
@endphp

<div
    x-data="chatbot({{ $config }})"
    x-init="init()"
    class="fixed bottom-6 right-6 z-50 flex flex-col items-end gap-3"
    role="complementary"
    aria-label="Assistant REZI"
>
    {{-- ==================== FENÊTRE DE CHAT ==================== --}}
    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 translate-y-4 scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0 scale-100"
        x-transition:leave-end="opacity-0 translate-y-4 scale-95"
        class="w-80 sm:w-96 bg-white rounded-2xl shadow-2xl border border-gray-200 flex flex-col overflow-hidden"
        style="max-height: 520px;"
    >
        {{-- Header --}}
        <div class="flex items-center gap-3 px-4 py-3 bg-orange-500 text-white rounded-t-2xl">
            <div class="w-9 h-9 rounded-full bg-white/20 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5-1-5z"/>
                </svg>
            </div>
            <div class="flex-1 min-w-0">
                <p class="font-semibold text-sm leading-tight">Assistant REZI</p>
                <p class="text-xs text-orange-100">Disponible 24h/24</p>
            </div>
            <button
                @click="toggle()"
                class="text-white/80 hover:text-white transition-colors"
                aria-label="Fermer le chat"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        {{-- Messages --}}
        <div
            x-ref="messagesList"
            class="flex-1 overflow-y-auto px-4 py-3 space-y-3 bg-gray-50"
            style="min-height: 260px; max-height: 320px;"
        >
            <template x-for="(msg, idx) in messages" :key="idx">
                <div
                    :class="msg.role === 'user'
                        ? 'flex justify-end'
                        : 'flex justify-start'"
                >
                    {{-- Avatar assistant --}}
                    <div x-show="msg.role === 'assistant'" class="w-6 h-6 rounded-full bg-orange-100 flex items-center justify-center shrink-0 mr-2 mt-0.5">
                        <span class="text-xs">🏠</span>
                    </div>

                    <div
                        :class="msg.role === 'user'
                            ? 'bg-orange-500 text-white rounded-2xl rounded-br-sm px-3 py-2 max-w-[75%] text-sm'
                            : msg.error
                                ? 'bg-red-50 text-red-700 border border-red-200 rounded-2xl rounded-bl-sm px-3 py-2 max-w-[80%] text-sm'
                                : 'bg-white text-gray-800 shadow-sm border border-gray-100 rounded-2xl rounded-bl-sm px-3 py-2 max-w-[80%] text-sm'"
                        x-html="formatMessage(msg.content)"
                    ></div>
                </div>
            </template>

            {{-- Indicateur de frappe --}}
            <div x-show="loading" class="flex justify-start">
                <div class="w-6 h-6 rounded-full bg-orange-100 flex items-center justify-center shrink-0 mr-2">
                    <span class="text-xs">🏠</span>
                </div>
                <div class="bg-white shadow-sm border border-gray-100 rounded-2xl rounded-bl-sm px-4 py-3">
                    <div class="flex gap-1">
                        <span class="w-2 h-2 bg-orange-400 rounded-full animate-bounce" style="animation-delay:0ms"></span>
                        <span class="w-2 h-2 bg-orange-400 rounded-full animate-bounce" style="animation-delay:150ms"></span>
                        <span class="w-2 h-2 bg-orange-400 rounded-full animate-bounce" style="animation-delay:300ms"></span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Suggestions (uniquement au début) --}}
        <div x-show="messages.length <= 1" class="px-4 py-2 flex flex-wrap gap-1.5 bg-gray-50 border-t border-gray-100">
            <template x-for="s in suggestions" :key="s">
                <button
                    @click="useSuggestion(s)"
                    class="text-xs px-2.5 py-1 rounded-full border border-orange-200 text-orange-600 hover:bg-orange-50 transition-colors whitespace-nowrap"
                    x-text="s"
                ></button>
            </template>
        </div>

        {{-- Zone de saisie --}}
        <div class="px-3 py-3 bg-white border-t border-gray-100 flex items-end gap-2">
            <textarea
                x-ref="input"
                x-model="input"
                @keydown="handleKey($event)"
                placeholder="Votre message..."
                rows="1"
                class="flex-1 resize-none rounded-xl border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-orange-400 focus:border-transparent transition bg-gray-50"
                style="max-height: 80px;"
                :disabled="loading"
                aria-label="Message au chatbot"
            ></textarea>
            <button
                @click="send()"
                :disabled="loading || !input.trim()"
                class="shrink-0 w-9 h-9 rounded-xl bg-orange-500 hover:bg-orange-600 disabled:opacity-40 disabled:cursor-not-allowed text-white flex items-center justify-center transition-colors"
                aria-label="Envoyer"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                </svg>
            </button>
        </div>
    </div>

    {{-- ==================== BOUTON FLOTTANT ==================== --}}
    <button
        @click="toggle()"
        class="w-14 h-14 rounded-full bg-orange-500 hover:bg-orange-600 text-white shadow-lg hover:shadow-xl transition-all duration-200 flex items-center justify-center relative group"
        :class="open ? 'rotate-0' : ''"
        aria-label="Ouvrir l'assistant REZI"
    >
        {{-- Icône chat / fermer --}}
        <svg x-show="!open" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5-1-5z"/>
        </svg>
        <svg x-show="open" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
        </svg>

        {{-- Pastille "nouveau" (visible quand fermé) --}}
        <span
            x-show="!open"
            class="absolute -top-1 -right-1 w-4 h-4 bg-green-400 rounded-full border-2 border-white"
            aria-hidden="true"
        ></span>

        {{-- Tooltip --}}
        <span class="absolute right-16 whitespace-nowrap bg-gray-900 text-white text-xs px-2.5 py-1 rounded-lg opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none" x-show="!open">
            Besoin d'aide ?
        </span>
    </button>
</div>
