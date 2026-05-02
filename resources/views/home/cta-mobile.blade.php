                {{-- CTA STICKY MOBILE --}}
                <div x-data="{ visible: false }" x-init="window.addEventListener('scroll', () => { visible = window.scrollY > 500 })" x-show="visible"
                    x-transition:enter="transition ease-out duration-300"
                    x-transition:enter-start="translate-y-full opacity-0" x-transition:enter-end="translate-y-0 opacity-100"
                    x-transition:leave="transition ease-in duration-200" x-transition:leave-start="translate-y-0 opacity-100"
                    x-transition:leave-end="translate-y-full opacity-0"
                    class="fixed bottom-16 md:bottom-0 left-0 right-0 z-40 lg:hidden bg-white border-t border-gray-200 shadow-2xl pb-safe"
                    x-cloak>
                    <div class="px-4 py-3 flex items-center gap-3">
                        {{-- Info rapide --}}
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-gray-900 truncate">Trouvez votre logement</p>
                            <p class="text-xs text-gray-500">{{ number_format($stats['residences'] ?? 0) }} résidences
                                disponibles</p>
                        </div>

                        {{-- Boutons d'action --}}
                        <a href="{{ route('residences.index') }}"
                            class="shrink-0 px-4 py-2.5 bg-[#ff385c] text-white text-sm font-semibold rounded-xl hover:bg-[#e00b41] transition-colors shadow-lg shadow-none flex items-center gap-2">
                            <svg aria-hidden="true" class="w-4 h-4" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            Rechercher
                        </a>
                        <a href="{{ route('residences.map') }}"
                            class="shrink-0 p-2.5 bg-gray-100 text-gray-700 rounded-xl hover:bg-gray-200 transition-colors"
                            title="Voir la carte">
                            <svg aria-hidden="true" class="w-5 h-5" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
                            </svg>
                        </a>
                    </div>
                </div>

