{{-- Footer Component — Single source of truth for all layouts --}}
<footer class="bg-white text-[#555555] mt-auto" role="contentinfo">

    @php
        $fc = \Illuminate\Support\Facades\Cache::remember('footer_all_settings', 3600,
            fn() => \App\Models\PlatformSetting::getByGroup('footer')
        );
        $platformEmail = \App\Models\PlatformSetting::getValue('platform_email', config('rezi.company.email'));
        $platformPhone = \App\Models\PlatformSetting::getValue('platform_phone', config('rezi.company.phone'));
        $platformPhoneTel = preg_replace('/[^\d+]/', '', (string) $platformPhone) ?: config('rezi.company.phone_raw');
        $platformPhoneWhatsapp = ltrim(preg_replace('/\D+/', '', (string) $platformPhone), '0') ?: ltrim(config('rezi.company.phone_raw'), '+');
    @endphp

    @if($fc['footer_newsletter_enabled'] ?? true)
    {{-- ─── Newsletter CTA ─── --}}
    <div class="bg-[#F16A00]">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 sm:py-12">
            <div class="flex flex-col md:flex-row items-center justify-between gap-6"
                x-data="newsletterForm('{{ route('newsletter.subscribe') }}', '{{ csrf_token() }}')">
                <div class="text-center md:text-left">
                    <h3
                        class="font-sans text-xl sm:text-2xl font-semibold text-white flex items-center justify-center md:justify-start gap-2">
                        <svg aria-hidden="true" class="w-6 h-6 shrink-0" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                        {{ $fc['footer_newsletter_title'] ?? 'Restez informé' }}
                    </h3>
                    <p class="text-red-100 mt-1 text-sm sm:text-base">{{ $fc['footer_newsletter_subtitle'] ?? 'Recevez les nouvelles résidences et offres exclusives directement dans votre boîte mail.' }}</p>
                </div>

                {{-- Formulaire --}}
                <div class="w-full md:w-auto">
                    {{-- État normal : formulaire --}}
                    <form x-show="!success" @submit.prevent="subscribe" class="flex flex-col sm:flex-row gap-3">
                        <div class="relative">
                            <input type="email" x-model="email" required :disabled="loading"
                                placeholder="Votre adresse email"
                                class="px-5 py-3 rounded-xl bg-white/20 border border-white/30 text-white placeholder-white/70 focus:outline-none focus:ring-2 focus:ring-white/50 focus:bg-white/25 w-full sm:w-72 transition disabled:opacity-50">
                        </div>
                        <button type="submit" :disabled="loading || !email"
                            class="px-6 py-3 bg-white text-[#F16A00] font-medium rounded-lg hover:bg-[#FFF4EB] active:scale-95 transition-all shadow-lg whitespace-nowrap disabled:opacity-60 flex items-center justify-center gap-2">
                            <svg aria-hidden="true" x-show="loading" class="animate-spin w-5 h-5" fill="none"
                                viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                    stroke-width="4" />
                                <path class="opacity-75" fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                            </svg>
                            <span x-text="loading ? 'Inscription...' : 'S\'abonner'"></span>
                        </button>
                    </form>

                    {{-- Message d'erreur --}}
                    <p x-show="error" x-text="error" x-transition
                        class="mt-2 text-sm text-white bg-red-500/30 backdrop-blur-sm rounded-lg px-4 py-2"></p>

                    {{-- Succès --}}
                    <div x-show="success" x-transition.duration.500ms
                        class="flex items-center gap-3 bg-white/20 backdrop-blur-sm rounded-xl px-5 py-3">
                        <div class="w-8 h-8 bg-white rounded-full flex items-center justify-center shrink-0">
                            <svg aria-hidden="true" class="w-5 h-5 text-green-500" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                        <p class="text-white font-medium text-sm" x-text="message"></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- ─── Main Content ─── --}}
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 lg:py-16">
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-12 gap-6 sm:gap-8 lg:gap-12">

            {{-- Brand + Description + Social --}}
            <div class="col-span-2 md:col-span-3 lg:col-span-4">
                <a href="{{ route('home') }}" class="inline-flex items-center gap-2 mb-4">
                    <img loading="lazy" src="{{ asset('images/logo-rezi.png') }}" alt="Rezi App" class="h-10 w-auto">
                </a>
                <p class="text-sm leading-relaxed max-w-sm mb-6">
                    {{ $fc['footer_brand_description'] ?? "La plateforme de référence pour trouver votre résidence meublée en Afrique de l'Ouest. Des centaines de logements vérifiés à portée de clic." }}
                </p>

                {{-- Social Links --}}
                <div class="flex flex-wrap gap-2 sm:gap-3">
                    @if($fc['footer_social_facebook_enabled'] ?? true)
                    <a href="{{ $fc['footer_social_facebook_url'] ?? config('rezi.social.facebook') }}" target="_blank" rel="noopener noreferrer"
                        aria-label="Facebook"
                        class="w-10 h-10 bg-[#f2f2f2] hover:bg-[#F16A00] rounded-full flex items-center justify-center transition-colors duration-200">
                        <svg aria-hidden="true" class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                            <path
                                d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z" />
                        </svg>
                    </a>
                    @endif
                    @if($fc['footer_social_instagram_enabled'] ?? true)
                    <a href="{{ $fc['footer_social_instagram_url'] ?? config('rezi.social.instagram') }}" target="_blank" rel="noopener noreferrer"
                        aria-label="Instagram"
                        class="w-10 h-10 bg-[#f2f2f2] hover:bg-[#F16A00] rounded-full flex items-center justify-center transition-colors duration-200">
                        <svg aria-hidden="true" class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                            <path
                                d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z" />
                        </svg>
                    </a>
                    @endif
                    @if($fc['footer_social_whatsapp_enabled'] ?? true)
                    <a href="{{ $fc['footer_social_whatsapp_url'] ?? 'https://wa.me/'.$platformPhoneWhatsapp }}" target="_blank"
                        rel="noopener noreferrer" aria-label="WhatsApp"
                        class="w-10 h-10 bg-[#f2f2f2] hover:bg-green-500 rounded-full flex items-center justify-center transition-colors duration-200">
                        <svg aria-hidden="true" class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                            <path
                                d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z" />
                        </svg>
                    </a>
                    @endif
                    @if($fc['footer_social_twitter_enabled'] ?? true)
                    <a href="{{ $fc['footer_social_twitter_url'] ?? 'https://twitter.com/rezi_ci' }}" target="_blank" rel="noopener noreferrer"
                        aria-label="X (Twitter)"
                        class="w-10 h-10 bg-[#f2f2f2] hover:bg-[#F16A00] rounded-full flex items-center justify-center transition-colors duration-200">
                        <svg aria-hidden="true" class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                            <path
                                d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z" />
                        </svg>
                    </a>
                    @endif
                    @if($fc['footer_social_linkedin_enabled'] ?? true)
                    <a href="{{ $fc['footer_social_linkedin_url'] ?? 'https://linkedin.com/company/rezi-ci' }}" target="_blank" rel="noopener noreferrer"
                        aria-label="LinkedIn"
                        class="w-10 h-10 bg-[#f2f2f2] hover:bg-blue-600 rounded-full flex items-center justify-center transition-colors duration-200">
                        <svg aria-hidden="true" class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                            <path
                                d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z" />
                        </svg>
                    </a>
                    @endif
                    @if($fc['footer_social_tiktok_enabled'] ?? true)
                    <a href="{{ $fc['footer_social_tiktok_url'] ?? config('rezi.social.tiktok') }}" target="_blank" rel="noopener noreferrer"
                        aria-label="TikTok"
                        class="w-10 h-10 bg-[#f2f2f2] hover:bg-[#0F0F0F] rounded-full flex items-center justify-center transition-colors duration-200">
                        <svg aria-hidden="true" class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                            <path
                                d="M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.99-.32-2.15-.23-3.02.37-.63.41-1.11 1.04-1.36 1.75-.21.51-.15 1.07-.14 1.61.24 1.64 1.82 3.02 3.5 2.87 1.12-.01 2.19-.66 2.77-1.61.19-.33.4-.67.41-1.06.1-1.79.06-3.57.07-5.36.01-4.03-.01-8.05.02-12.07z" />
                        </svg>
                    </a>
                    @endif
                </div>
            </div>

            {{-- Navigation --}}
            <div class="lg:col-span-2">
                <h4 class="text-sm font-semibold text-[#0F0F0F] mb-4">Explorer</h4>
                <ul class="space-y-1 text-sm">
                    <li><a href="{{ route('home') }}"
                            class="hover:text-[#F16A00] transition-colors block py-1.5">Accueil</a></li>
                    <li><a href="{{ route('residences.index') }}"
                            class="hover:text-[#F16A00] transition-colors block py-1.5">Toutes les résidences</a></li>
                    <li><a href="{{ route('residences.map') }}"
                            class="hover:text-[#F16A00] transition-colors block py-1.5">Carte
                            interactive</a></li>
                    <li><a href="{{ route('pages.about') }}"
                            class="hover:text-[#F16A00] transition-colors block py-1.5">À
                            propos</a></li>
                    <li><a href="{{ route('pages.faq') }}"
                            class="hover:text-[#F16A00] transition-colors block py-1.5">FAQ</a>
                    </li>
                </ul>
            </div>

            {{-- Propriétaires --}}
            <div class="lg:col-span-2">
                <h4 class="text-sm font-semibold text-[#0F0F0F] mb-4">Propriétaires</h4>
                <ul class="space-y-3 text-sm">
                    @auth
                        @if (Auth::user()->isOwner())
                            <li><a href="{{ route('owner.dashboard') }}"
                                    class="hover:text-[#F16A00] transition-colors">Tableau de bord</a></li>
                            <li><a href="{{ route('owner.residences.create') }}"
                                    class="hover:text-[#F16A00] transition-colors">Publier une annonce</a></li>
                            <li><a href="{{ route('owner.residences.index') }}"
                                    class="hover:text-[#F16A00] transition-colors">Mes résidences</a></li>
                        @else
                            <li><a href="{{ route('register') }}" class="hover:text-[#F16A00] transition-colors">Devenir
                                    propriétaire</a></li>
                        @endif
                    @else
                        <li><a href="{{ route('register') }}" class="hover:text-[#F16A00] transition-colors">Devenir
                                propriétaire</a></li>
                        <li><a href="{{ route('login') }}" class="hover:text-[#F16A00] transition-colors">Se
                                connecter</a></li>
                    @endauth
                    <li><a href="{{ route('pages.guide-proprietaire') }}"
                            class="hover:text-[#F16A00] transition-colors">Guide propriétaire</a></li>
                </ul>
            </div>

            {{-- Locataires --}}
            <div class="lg:col-span-2">
                <h4 class="text-sm font-semibold text-[#0F0F0F] mb-4">Locataires</h4>
                <ul class="space-y-1 text-sm">
                    @auth
                        <li><a href="{{ route('client.dashboard') }}" class="hover:text-[#F16A00] transition-colors block py-1.5">Mon espace</a></li>
                        <li><a href="{{ route('favorites.index') }}" class="hover:text-[#F16A00] transition-colors block py-1.5">Mes favoris</a></li>
                        <li><a href="{{ route('client.contacts') }}" class="hover:text-[#F16A00] transition-colors block py-1.5">Mes demandes</a></li>
                        <li><a href="{{ route('client.contracts') }}" class="hover:text-[#F16A00] transition-colors block py-1.5">Mes contrats</a></li>
                    @else
                        <li><a href="{{ route('register') }}" class="hover:text-[#F16A00] transition-colors block py-1.5">Créer un compte</a></li>
                        <li><a href="{{ route('login') }}" class="hover:text-[#F16A00] transition-colors block py-1.5">Se connecter</a></li>
                    @endauth
                    <li><a href="{{ route('residences.index') }}" class="hover:text-[#F16A00] transition-colors block py-1.5">Trouver un logement</a></li>
                </ul>
            </div>

            {{-- Contact + Horaires --}}
            <div class="col-span-2 lg:col-span-2">
                <h4 class="text-sm font-semibold text-[#0F0F0F] mb-4">Contact</h4>
                <ul class="space-y-4 text-sm">
                    <li class="flex items-start gap-3">
                        <div class="w-9 h-9 bg-[#f2f2f2] rounded-lg flex items-center justify-center shrink-0 mt-0.5">
                            <svg aria-hidden="true" class="w-4 h-4 text-[#F16A00]" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-gray-300">Cocody, Riviera Palmeraie</p>
                            <p>{{ config('rezi.company.address', "Abidjan, Côte d'Ivoire") }}</p>
                        </div>
                    </li>
                    <li class="flex items-center gap-3">
                        <div class="w-9 h-9 bg-[#f2f2f2] rounded-lg flex items-center justify-center shrink-0">
                            <svg aria-hidden="true" class="w-4 h-4 text-[#F16A00]" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <a href="mailto:{{ $platformEmail }}"
                            class="hover:text-[#F16A00] transition-colors">{{ $platformEmail }}</a>
                    </li>
                    <li class="flex items-center gap-3">
                        <div class="w-9 h-9 bg-[#f2f2f2] rounded-lg flex items-center justify-center shrink-0">
                            <svg aria-hidden="true" class="w-4 h-4 text-[#F16A00]" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                            </svg>
                        </div>
                        <a href="tel:{{ $platformPhoneTel }}"
                            class="hover:text-[#F16A00] transition-colors">{{ $platformPhone }}</a>
                    </li>
                    @if($fc['footer_support_enabled'] ?? true)
                    <li class="flex items-center gap-3">
                        <div class="w-9 h-9 bg-[#f2f2f2] rounded-lg flex items-center justify-center shrink-0">
                            <svg aria-hidden="true" class="w-4 h-4 text-[#F16A00]" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <span class="flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-green-400 animate-pulse"></span>
                                <span class="text-green-400 text-xs font-medium">{{ $fc['footer_support_text'] ?? 'Support en ligne 24/7' }}</span>
                            </span>
                        </div>
                    </li>
                    @endif
                </ul>
            </div>
        </div>
    </div>

    {{-- ─── Bottom Bar ─── --}}
    <div class="border-t border-[#F2F2F2]">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <div class="flex flex-col lg:flex-row items-center justify-between gap-4">
                {{-- Copyright --}}
                <p class="text-xs text-[#555555] text-center lg:text-left">
                    © {{ date('Y') }} Rezi App. Tous droits réservés.
                </p>

                {{-- Legal Links --}}
                <div class="flex flex-wrap items-center justify-center gap-4 sm:gap-6 text-xs">
                    <a href="{{ route('pages.cgu') }}"
                        class="text-gray-500 hover:text-[#F16A00] transition-colors">Conditions d'utilisation</a>
                    <a href="{{ route('pages.confidentialite') }}"
                        class="text-gray-500 hover:text-[#F16A00] transition-colors">Confidentialité</a>
                    <a href="{{ route('pages.mentions-legales') }}"
                        class="text-gray-500 hover:text-[#F16A00] transition-colors">Mentions légales</a>
                </div>

                {{-- Payment Methods --}}
                <div class="flex items-center gap-3">
                    <span class="text-[#555555] text-xs">Paiements sécurisés</span>
                    <div class="flex gap-1.5">
                        <div
                            class="h-7 px-2 bg-[#f2f2f2] rounded-md flex items-center justify-center border border-[#F2F2F2]">
                            <span class="text-xs text-[#555555]">💳 Visa</span>
                        </div>
                        <div
                            class="h-7 px-2 bg-[#f2f2f2] rounded-md flex items-center justify-center border border-[#F2F2F2]">
                            <span class="text-xs font-bold text-[#F16A00]">OM</span>
                        </div>
                        <div
                            class="h-7 px-2 bg-[#f2f2f2] rounded-md flex items-center justify-center border border-[#F2F2F2]">
                            <span class="text-xs font-bold text-yellow-500">MTN</span>
                        </div>
                        <div
                            class="h-7 px-2 bg-[#f2f2f2] rounded-md flex items-center justify-center border border-[#F2F2F2]">
                            <span class="text-xs font-bold text-blue-400">Wave</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    {{-- ─── Retour en haut ─── --}}
        <div x-data="scrollReveal(400)"
         x-init="init()"
         x-cloak
         class="fixed bottom-6 right-6 z-50">
        <button x-show="visible"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-3"
                x-transition:enter-end="opacity-100 translate-y-0"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
            @click="scrollToTop()"
                aria-label="Retour en haut de page"
                class="w-11 h-11 bg-[#F16A00] hover:bg-[#CC5A00] text-white rounded-full shadow-lg hover:shadow-xl flex items-center justify-center transition-all active:scale-95">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 15.75l7.5-7.5 7.5 7.5"/>
            </svg>
        </button>
    </div>
</footer>
