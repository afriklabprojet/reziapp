<x-app-layout>
    @section('title', $metaTitle ?? $content['title'] ?? "Guide Propriétaire - Rezi App")
    @section('description', $metaDescription ?? "Guide complet pour les propriétaires sur Rezi App.")

    <div class="min-h-screen bg-gray-50">

        {{-- ===== Hero ===== --}}
        <div class="bg-linear-to-br from-[#F16A00] via-[#F16A00] to-[#CC5A00]">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
                <nav class="text-sm text-[#FFE7D1] mb-4 flex items-center gap-1.5">
                    <a href="{{ route('home') }}" class="hover:text-white transition">Accueil</a>
                    <svg class="w-3.5 h-3.5 opacity-60" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    <span class="text-white">Guide Propriétaire</span>
                </nav>
                <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-6">
                    <div>
                        <h1 class="text-3xl sm:text-4xl font-bold text-white">{{ $content['title'] ?? "Guide du Propriétaire" }}</h1>
                        <p class="mt-3 text-[#FFE7D1] text-lg max-w-xl">{{ $content['subtitle'] ?? "Tout ce que vous devez savoir pour réussir sur Rezi App" }}</p>
                    </div>
                    <div class="shrink-0 flex items-center gap-2 bg-white/20 backdrop-blur-sm rounded-2xl px-5 py-3 text-white text-sm font-medium whitespace-nowrap">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Lecture : ~5 min
                    </div>
                </div>

                {{-- Stats rapides --}}
                <div class="mt-10 grid grid-cols-3 gap-4">
                    <div class="bg-white/15 backdrop-blur-sm rounded-2xl p-4 text-center">
                        <div class="text-2xl font-bold text-white">100%</div>
                        <div class="text-xs text-[#FFE7D1] mt-0.5">Publication gratuite</div>
                    </div>
                    <div class="bg-white/15 backdrop-blur-sm rounded-2xl p-4 text-center">
                        <div class="text-2xl font-bold text-white">0%</div>
                        <div class="text-xs text-[#FFE7D1] mt-0.5">Commission Rezi App</div>
                    </div>
                    <div class="bg-white/15 backdrop-blur-sm rounded-2xl p-4 text-center">
                        <div class="text-2xl font-bold text-white">24h</div>
                        <div class="text-xs text-[#FFE7D1] mt-0.5">Délai modération</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ===== Introduction ===== --}}
        @if(!empty($content['introduction']))
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 pt-8">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 sm:p-8 flex gap-4">
                <div class="shrink-0 w-10 h-10 bg-[#FFE7D1] rounded-xl flex items-center justify-center">
                    <svg class="w-5 h-5 text-[#CC5A00]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <p class="text-gray-600 leading-relaxed text-base">{{ $content['introduction'] }}</p>
            </div>
        </div>
        @endif

        {{-- ===== Sommaire ===== --}}
        @if(!empty($content['steps']))
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 pt-6">
            <div class="bg-[#FFF4EB] border border-[#FFE7D1] rounded-2xl p-5">
                <p class="text-xs font-semibold text-[#A34700] uppercase tracking-wider mb-3">Sommaire</p>
                <div class="grid sm:grid-cols-2 gap-x-6 gap-y-1.5">
                    @foreach($content['steps'] as $i => $step)
                    <a href="#step-{{ $i + 1 }}" class="flex items-center gap-2 text-sm text-[#8e0730] hover:text-[#CC5A00] transition group">
                        <span class="w-5 h-5 bg-[#FFD0A3] rounded-full flex items-center justify-center text-xs font-bold text-[#A34700] shrink-0 group-hover:bg-[#FFB46F] transition">{{ $i + 1 }}</span>
                        {{ $step['title'] }}
                    </a>
                    @endforeach
                </div>
            </div>
        </div>
        @endif

        {{-- ===== Steps ===== --}}
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="space-y-8">
                @foreach($content['steps'] ?? [] as $stepIndex => $step)
                <div id="step-{{ $stepIndex + 1 }}" class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden scroll-mt-6">

                    {{-- Step Header --}}
                    <div class="bg-linear-to-r from-[#F16A00] to-[#CC5A00] p-6">
                        <div class="flex items-start gap-4">
                            <span class="shrink-0 w-11 h-11 bg-white/20 text-white rounded-xl flex items-center justify-center font-bold text-lg">{{ $stepIndex + 1 }}</span>
                            <div>
                                <h2 class="text-xl font-bold text-white">{{ $step['title'] }}</h2>
                                @if(!empty($step['description']))
                                <p class="text-[#FFE7D1] mt-1 text-sm leading-relaxed">{{ $step['description'] }}</p>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="p-6 space-y-5">

                        {{-- Substeps --}}
                        @if(!empty($step['substeps']))
                        <div class="space-y-2.5">
                            @foreach($step['substeps'] as $idx => $substep)
                            <div class="flex items-start gap-3">
                                <span class="shrink-0 w-6 h-6 bg-[#FFE7D1] text-[#A34700] rounded-full flex items-center justify-center text-xs font-bold mt-0.5">{{ $idx + 1 }}</span>
                                <span class="text-gray-700 text-sm leading-relaxed">{{ $substep }}</span>
                            </div>
                            @endforeach
                        </div>
                        @endif

                        {{-- Tips --}}
                        @if(!empty($step['tips']))
                        <div class="bg-blue-50 rounded-xl p-4 border border-blue-100">
                            <h4 class="font-semibold text-blue-900 mb-2.5 flex items-center gap-2 text-sm">
                                <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                                </svg>
                                Conseils pro
                            </h4>
                            <ul class="space-y-1.5">
                                @foreach($step['tips'] as $tip)
                                <li class="flex items-start gap-2 text-blue-800 text-sm">
                                    <svg class="w-4 h-4 text-blue-500 shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                    {{ $tip }}
                                </li>
                                @endforeach
                            </ul>
                        </div>
                        @endif

                        {{-- Tools --}}
                        @if(!empty($step['tools']))
                        <div class="grid sm:grid-cols-2 gap-3">
                            @foreach($step['tools'] as $tool)
                            <div class="flex items-start gap-3 bg-gray-50 rounded-xl p-4 border border-gray-100">
                                <span class="text-2xl shrink-0">{{ $tool['icon'] }}</span>
                                <div>
                                    <div class="font-semibold text-gray-900 text-sm">{{ $tool['name'] }}</div>
                                    <p class="text-xs text-gray-500 mt-0.5 leading-relaxed">{{ $tool['description'] }}</p>
                                </div>
                            </div>
                            @endforeach
                        </div>
                        @endif

                    </div>
                </div>
                @endforeach
            </div>
        </div>

        {{-- ===== FAQ ===== --}}
        @if(!empty($content['faq']))
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 pb-8">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-5 border-b border-gray-100">
                    <h2 class="text-xl font-bold text-gray-900 flex items-center gap-2">
                        <svg class="w-5 h-5 text-[#F16A00]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Questions fréquentes
                    </h2>
                </div>
                <div class="divide-y divide-gray-100" x-data="{ open: null }">
                    @foreach($content['faq'] as $faqIndex => $item)
                    <div class="px-6 py-4">
                        <button @click="open = open === {{ $faqIndex }} ? null : {{ $faqIndex }}"
                            class="w-full flex items-start justify-between gap-4 text-left">
                            <span class="font-medium text-gray-900 text-sm">{{ $item['question'] }}</span>
                            <svg class="w-5 h-5 text-gray-400 shrink-0 mt-0.5 transition-transform duration-200"
                                :class="open === {{ $faqIndex }} ? 'rotate-180' : ''"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        <div x-show="open === {{ $faqIndex }}" x-collapse class="mt-3">
                            <p class="text-sm text-gray-600 leading-relaxed">{{ $item['answer'] }}</p>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
        @endif

        {{-- ===== CTA ===== --}}
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 pb-16">
            <div class="bg-linear-to-br from-[#F16A00] to-[#CC5A00] rounded-2xl p-8 sm:p-10 text-center">
                <div class="w-14 h-14 bg-white/20 rounded-2xl flex items-center justify-center mx-auto mb-4">
                    <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                </div>
                <h3 class="text-2xl font-bold text-white mb-2">Prêt à publier votre résidence ?</h3>
                <p class="text-[#FFE7D1] mb-8 max-w-md mx-auto">Publication gratuite, sans commission. Rejoignez les propriétaires qui font confiance à Rezi App.</p>
                <div class="flex flex-col sm:flex-row items-center justify-center gap-3">
                    @auth
                    <a href="{{ route('owner.residences.create') }}"
                        class="inline-flex items-center gap-2 px-6 py-3 bg-white text-[#CC5A00] font-semibold rounded-xl hover:bg-[#FFF4EB] transition shadow-sm">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        Publier une résidence
                    </a>
                    <a href="{{ route('owner.dashboard') }}"
                        class="inline-flex items-center gap-2 px-6 py-3 bg-white/20 text-white font-semibold rounded-xl hover:bg-white/30 transition">
                        Mon tableau de bord
                    </a>
                    @else
                    <a href="{{ route('register') }}"
                        class="inline-flex items-center gap-2 px-6 py-3 bg-white text-[#CC5A00] font-semibold rounded-xl hover:bg-[#FFF4EB] transition shadow-sm">
                        Créer un compte gratuitement
                    </a>
                    <a href="{{ route('login') }}"
                        class="inline-flex items-center gap-2 px-6 py-3 bg-white/20 text-white font-semibold rounded-xl hover:bg-white/30 transition">
                        J'ai déjà un compte
                    </a>
                    @endauth
                </div>
            </div>
        </div>

    </div>
</x-app-layout>
