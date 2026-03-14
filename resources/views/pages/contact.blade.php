<x-app-layout>
    @section('title', $metaTitle)
    @section('description', $metaDescription)

    @php
        $hero = $content['hero'] ?? [];
        $cards = $content['cards'] ?? [];
        $faq = $content['faq'] ?? [];
        $hours = $content['hours'] ?? [];
        $cta = $content['cta'] ?? [];
    @endphp

    <div class="min-h-screen bg-gray-50">

        {{-- HERO --}}
        <section class="relative overflow-hidden bg-linear-to-br from-gray-900 via-gray-800 to-gray-900 text-white">
            <div class="absolute inset-0 opacity-10" aria-hidden="true">
                <div class="absolute -top-24 -right-24 w-96 h-96 rounded-full bg-orange-500 blur-3xl"></div>
                <div class="absolute bottom-0 left-0 w-72 h-72 rounded-full bg-blue-400 blur-3xl"></div>
            </div>

            <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 sm:py-24">
                <div class="max-w-3xl">
                    <nav class="text-sm text-gray-400 mb-6" aria-label="Breadcrumb">
                        <a href="{{ route('home') }}" class="hover:text-white transition">Accueil</a>
                        <span class="mx-2">›</span>
                        <span class="text-orange-400">Nous contacter</span>
                    </nav>
                    <h1 class="text-3xl sm:text-5xl lg:text-6xl font-extrabold leading-tight tracking-tight">
                        {{ $hero['title'] ?? '' }}
                        <span
                            class="text-transparent bg-clip-text bg-linear-to-r from-orange-400 to-orange-500">{{ $hero['highlight'] ?? '' }}</span>
                    </h1>
                    <p class="mt-6 text-lg sm:text-xl text-gray-300 leading-relaxed max-w-2xl">
                        {{ $hero['description'] ?? '' }}
                    </p>
                </div>
            </div>
        </section>

        {{-- CARTES DE CONTACT --}}
        <section class="relative -mt-8 sm:-mt-12 z-10">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
                    {{-- Email --}}
                    <a href="mailto:{{ $cards['email'] ?? config('rezi.company.email') }}"
                        class="bg-white rounded-2xl shadow-lg border border-gray-100 p-6 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 group block">
                        <div
                            class="w-12 h-12 bg-orange-100 rounded-xl flex items-center justify-center mb-4 group-hover:bg-orange-500 transition-colors">
                            <svg class="w-6 h-6 text-orange-500 group-hover:text-white transition-colors" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <h3 class="font-bold text-gray-900 mb-1">Email</h3>
                        <p class="text-orange-500 text-sm font-medium group-hover:underline">
                            {{ $cards['email'] ?? config('rezi.company.email') }}</p>
                        <p class="text-xs text-gray-400 mt-2">{{ $cards['email_subtitle'] ?? 'Réponse sous 24h' }}</p>
                    </a>

                    {{-- Téléphone --}}
                    <a href="tel:{{ $cards['phone_raw'] ?? config('rezi.company.phone_raw') }}"
                        class="bg-white rounded-2xl shadow-lg border border-gray-100 p-6 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 group block">
                        <div
                            class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center mb-4 group-hover:bg-blue-500 transition-colors">
                            <svg class="w-6 h-6 text-blue-500 group-hover:text-white transition-colors" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                            </svg>
                        </div>
                        <h3 class="font-bold text-gray-900 mb-1">Téléphone</h3>
                        <p class="text-blue-500 text-sm font-medium group-hover:underline">
                            {{ $cards['phone'] ?? config('rezi.company.phone') }}</p>
                        <p class="text-xs text-gray-400 mt-2">{{ $cards['phone_subtitle'] ?? 'Lun – Sam · 8h – 18h' }}
                        </p>
                    </a>

                    {{-- WhatsApp --}}
                    <a href="https://wa.me/{{ $cards['whatsapp_number'] ?? str_replace('+', '', config('rezi.company.phone_raw')) }}"
                        target="_blank" rel="noopener"
                        class="bg-white rounded-2xl shadow-lg border border-gray-100 p-6 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 group block">
                        <div
                            class="w-12 h-12 bg-emerald-100 rounded-xl flex items-center justify-center mb-4 group-hover:bg-emerald-500 transition-colors">
                            <svg class="w-6 h-6 text-emerald-500 group-hover:text-white transition-colors"
                                fill="currentColor" viewBox="0 0 24 24">
                                <path
                                    d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981z" />
                            </svg>
                        </div>
                        <h3 class="font-bold text-gray-900 mb-1">WhatsApp</h3>
                        <p class="text-emerald-500 text-sm font-medium group-hover:underline">
                            {{ $cards['whatsapp_label'] ?? 'Discuter maintenant' }}</p>
                        <p class="text-xs text-gray-400 mt-2">{{ $cards['whatsapp_subtitle'] ?? 'Réponse rapide' }}</p>
                    </a>

                    {{-- Adresse --}}
                    <div
                        class="bg-white rounded-2xl shadow-lg border border-gray-100 p-6 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 group">
                        <div
                            class="w-12 h-12 bg-purple-100 rounded-xl flex items-center justify-center mb-4 group-hover:bg-purple-500 transition-colors">
                            <svg class="w-6 h-6 text-purple-500 group-hover:text-white transition-colors" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </div>
                        <h3 class="font-bold text-gray-900 mb-1">Bureau</h3>
                        <p class="text-gray-600 text-sm font-medium">
                            {{ $cards['address_line1'] ?? 'Cocody, Riviera Palmeraie' }}</p>
                        <p class="text-xs text-gray-400 mt-2">
                            {{ $cards['address_line2'] ?? config('rezi.company.address', "Abidjan, Côte d'Ivoire") }}
                        </p>
                    </div>
                </div>
            </div>
        </section>

        {{-- FAQ --}}
        <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 sm:py-24">
            <div class="grid lg:grid-cols-5 gap-12 lg:gap-16">
                {{-- Intro colonne --}}
                <div class="lg:col-span-2">
                    <div
                        class="inline-flex items-center gap-2 bg-orange-100 text-orange-700 px-4 py-1.5 rounded-full text-xs font-bold uppercase tracking-wider mb-6">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 00-.867.5 1 1 0 11-1.731-1A3 3 0 0113 8a3.001 3.001 0 01-2 2.83V11a1 1 0 11-2 0v-1a1 1 0 011-1 1 1 0 100-2zm0 8a1 1 0 100-2 1 1 0 000 2z"
                                clip-rule="evenodd" />
                        </svg>
                        Questions fréquentes
                    </div>
                    <h2 class="text-3xl sm:text-4xl font-extrabold text-gray-900 leading-tight mb-4">
                        {{ $faq['title'] ?? 'On répond à vos questions' }}
                    </h2>
                    <p class="text-gray-500 leading-relaxed mb-6">
                        {{ $faq['subtitle'] ?? 'Retrouvez les réponses aux questions les plus courantes.' }}
                    </p>
                    <a href="{{ route('pages.faq') }}"
                        class="inline-flex items-center gap-2 text-orange-500 font-semibold hover:text-orange-600 transition group">
                        Voir toute la FAQ
                        <svg class="w-4 h-4 group-hover:translate-x-1 transition-transform" fill="none"
                            stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 8l4 4m0 0l-4 4m4-4H3" />
                        </svg>
                    </a>
                </div>

                {{-- Accordéon --}}
                <div class="lg:col-span-3 space-y-3" x-data="{ open: 1 }">
                    @foreach ($faq['items'] ?? [] as $i => $faqItem)
                        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden transition-shadow"
                            :class="open === {{ $i + 1 }} ? 'shadow-md border-orange-200' : ''">
                            <button class="w-full flex items-center justify-between p-5 sm:p-6 text-left gap-4"
                                @click="open = open === {{ $i + 1 }} ? null : {{ $i + 1 }}"
                                :aria-expanded="open === {{ $i + 1 }}">
                                <span class="font-semibold text-gray-900">{{ $faqItem['question'] }}</span>
                                <span
                                    class="shrink-0 w-8 h-8 rounded-full flex items-center justify-center transition-colors"
                                    :class="open === {{ $i + 1 }} ? 'bg-orange-500 text-white' :
                                        'bg-gray-100 text-gray-400'">
                                    <svg class="w-4 h-4 transition-transform duration-200"
                                        :class="open === {{ $i + 1 }} ? 'rotate-180' : ''" fill="none"
                                        stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 9l-7 7-7-7" />
                                    </svg>
                                </span>
                            </button>
                            <div x-show="open === {{ $i + 1 }}" x-collapse>
                                <div class="px-5 sm:px-6 pb-5 sm:pb-6 text-gray-500 leading-relaxed">
                                    {{ $faqItem['answer'] }}
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- HORAIRES & RÉSEAUX --}}
        <section class="bg-white py-16 sm:py-24">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid md:grid-cols-2 gap-8 lg:gap-12">
                    {{-- Horaires --}}
                    <div class="bg-gray-50 rounded-3xl p-8 sm:p-10">
                        <div
                            class="inline-flex items-center gap-2 bg-blue-100 text-blue-700 px-4 py-1.5 rounded-full text-xs font-bold uppercase tracking-wider mb-6">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z"
                                    clip-rule="evenodd" />
                            </svg>
                            Horaires d'ouverture
                        </div>
                        <h3 class="text-2xl font-extrabold text-gray-900 mb-6">Quand nous joindre</h3>

                        <div class="space-y-3">
                            @foreach ($hours['items'] ?? [] as $h)
                                <div
                                    class="flex items-center justify-between bg-white rounded-xl px-5 py-3.5 border border-gray-100">
                                    <span class="font-medium text-gray-900">{{ $h['day'] }}</span>
                                    <span
                                        class="flex items-center gap-2 text-sm {{ $h['open'] ?? false ? 'text-gray-600' : 'text-gray-400' }}">
                                        <span
                                            class="w-2 h-2 rounded-full {{ $h['open'] ?? false ? 'bg-emerald-400' : 'bg-gray-300' }}"></span>
                                        {{ $h['hours'] }}
                                    </span>
                                </div>
                            @endforeach
                        </div>

                        @if (!empty($hours['note']))
                            <div class="mt-6 p-4 bg-orange-50 rounded-xl border border-orange-100">
                                <div class="flex gap-3">
                                    <svg class="w-5 h-5 text-orange-500 shrink-0 mt-0.5" fill="currentColor"
                                        viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                            d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                                            clip-rule="evenodd" />
                                    </svg>
                                    <p class="text-sm text-orange-700">{{ $hours['note'] }}</p>
                                </div>
                            </div>
                        @endif
                    </div>

                    {{-- Réseaux sociaux --}}
                    <div class="bg-gray-50 rounded-3xl p-8 sm:p-10">
                        <div
                            class="inline-flex items-center gap-2 bg-purple-100 text-purple-700 px-4 py-1.5 rounded-full text-xs font-bold uppercase tracking-wider mb-6">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path
                                    d="M15 8a3 3 0 10-2.977-2.63l-4.94 2.47a3 3 0 100 4.319l4.94 2.47a3 3 0 10.895-1.789l-4.94-2.47a3.027 3.027 0 000-.74l4.94-2.47C13.456 7.68 14.19 8 15 8z" />
                            </svg>
                            Retrouvez-nous
                        </div>
                        <h3 class="text-2xl font-extrabold text-gray-900 mb-6">Sur les réseaux sociaux</h3>

                        <div class="space-y-3">
                            <a href="{{ config('rezi.social.facebook') }}" target="_blank" rel="noopener"
                                class="flex items-center gap-4 bg-white rounded-xl px-5 py-4 border border-gray-100 hover:border-blue-200 hover:shadow-md transition-all group">
                                <div
                                    class="w-11 h-11 bg-blue-100 rounded-xl flex items-center justify-center group-hover:bg-blue-600 transition-colors">
                                    <svg class="w-5 h-5 text-blue-600 group-hover:text-white transition-colors"
                                        fill="currentColor" viewBox="0 0 24 24">
                                        <path
                                            d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z" />
                                    </svg>
                                </div>
                                <div class="min-w-0">
                                    <p class="font-semibold text-gray-900">Facebook</p>
                                    <p class="text-sm text-gray-400">@rezi.ci</p>
                                </div>
                                <svg class="w-5 h-5 text-gray-300 ml-auto group-hover:text-blue-500 group-hover:translate-x-1 transition-all"
                                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 5l7 7-7 7" />
                                </svg>
                            </a>

                            <a href="{{ config('rezi.social.instagram') }}" target="_blank" rel="noopener"
                                class="flex items-center gap-4 bg-white rounded-xl px-5 py-4 border border-gray-100 hover:border-pink-200 hover:shadow-md transition-all group">
                                <div
                                    class="w-11 h-11 bg-pink-100 rounded-xl flex items-center justify-center group-hover:bg-pink-600 transition-colors">
                                    <svg class="w-5 h-5 text-pink-600 group-hover:text-white transition-colors"
                                        fill="currentColor" viewBox="0 0 24 24">
                                        <path
                                            d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z" />
                                    </svg>
                                </div>
                                <div class="min-w-0">
                                    <p class="font-semibold text-gray-900">Instagram</p>
                                    <p class="text-sm text-gray-400">@rezi.ci</p>
                                </div>
                                <svg class="w-5 h-5 text-gray-300 ml-auto group-hover:text-pink-500 group-hover:translate-x-1 transition-all"
                                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 5l7 7-7 7" />
                                </svg>
                            </a>

                            <a href="{{ config('rezi.social.tiktok') }}" target="_blank" rel="noopener"
                                class="flex items-center gap-4 bg-white rounded-xl px-5 py-4 border border-gray-100 hover:border-gray-300 hover:shadow-md transition-all group">
                                <div
                                    class="w-11 h-11 bg-gray-100 rounded-xl flex items-center justify-center group-hover:bg-gray-900 transition-colors">
                                    <svg class="w-5 h-5 text-gray-900 group-hover:text-white transition-colors"
                                        fill="currentColor" viewBox="0 0 24 24">
                                        <path
                                            d="M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.99-.32-2.15-.23-3.02.37-.63.41-1.11 1.04-1.36 1.75-.21.51-.15 1.07-.14 1.61.24 1.64 1.82 3.02 3.5 2.87 1.12-.01 2.19-.66 2.77-1.61.19-.33.4-.67.41-1.06.1-1.79.06-3.57.07-5.36.01-4.03-.01-8.05.02-12.07z" />
                                    </svg>
                                </div>
                                <div class="min-w-0">
                                    <p class="font-semibold text-gray-900">TikTok</p>
                                    <p class="text-sm text-gray-400">@rezi.ci</p>
                                </div>
                                <svg class="w-5 h-5 text-gray-300 ml-auto group-hover:text-gray-900 group-hover:translate-x-1 transition-all"
                                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 5l7 7-7 7" />
                                </svg>
                            </a>

                            <a href="https://wa.me/{{ str_replace('+', '', config('rezi.company.phone_raw')) }}"
                                target="_blank" rel="noopener"
                                class="flex items-center gap-4 bg-white rounded-xl px-5 py-4 border border-gray-100 hover:border-emerald-200 hover:shadow-md transition-all group">
                                <div
                                    class="w-11 h-11 bg-emerald-100 rounded-xl flex items-center justify-center group-hover:bg-emerald-500 transition-colors">
                                    <svg class="w-5 h-5 text-emerald-500 group-hover:text-white transition-colors"
                                        fill="currentColor" viewBox="0 0 24 24">
                                        <path
                                            d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981z" />
                                    </svg>
                                </div>
                                <div class="min-w-0">
                                    <p class="font-semibold text-gray-900">WhatsApp</p>
                                    <p class="text-sm text-gray-400">Discuter maintenant</p>
                                </div>
                                <svg class="w-5 h-5 text-gray-300 ml-auto group-hover:text-emerald-500 group-hover:translate-x-1 transition-all"
                                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 5l7 7-7 7" />
                                </svg>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- CTA FINAL --}}
        <section class="py-16 sm:py-24">
            <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
                <div
                    class="relative overflow-hidden bg-linear-to-br from-gray-900 via-gray-800 to-gray-900 rounded-3xl p-8 sm:p-14 text-center text-white">
                    <div class="absolute top-0 right-0 w-64 h-64 bg-orange-500/10 rounded-full blur-3xl"
                        aria-hidden="true"></div>
                    <div class="absolute bottom-0 left-0 w-48 h-48 bg-blue-500/10 rounded-full blur-3xl"
                        aria-hidden="true"></div>

                    <div class="relative">
                        <h2 class="text-2xl sm:text-4xl font-extrabold mb-4">{{ $cta['title'] ?? '' }}</h2>
                        <p class="text-gray-300 mb-8 max-w-xl mx-auto text-lg">{{ $cta['description'] ?? '' }}</p>
                        <div class="flex flex-col sm:flex-row gap-4 justify-center">
                            <a href="{{ route('pages.guide-proprietaire') }}"
                                class="inline-flex items-center justify-center gap-2 px-8 py-3.5 bg-orange-500 hover:bg-orange-600 text-white rounded-xl font-semibold transition-all shadow-lg shadow-orange-500/25 active:scale-95">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                                </svg>
                                {{ $cta['cta_primary'] ?? 'Guide propriétaire' }}
                            </a>
                            <a href="{{ route('register') }}"
                                class="inline-flex items-center justify-center gap-2 px-8 py-3.5 bg-white/10 hover:bg-white/20 text-white rounded-xl font-semibold transition-all border border-white/20 active:scale-95">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                                </svg>
                                {{ $cta['cta_secondary'] ?? 'Créer un compte' }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
</x-app-layout>
