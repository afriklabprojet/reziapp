<x-app-layout>
    @section('title', $metaTitle)
    @section('description', $metaDescription)

    @php
        $hero = $content['hero'] ?? [];
        $mission = $content['mission'] ?? [];
        $steps = $content['steps'] ?? [];
        $values = $content['values'] ?? [];
        $why = $content['why'] ?? [];
        $cta = $content['cta'] ?? [];

        $featureIcons = [
            'orange' =>
                '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />',
            'emerald' =>
                '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />',
            'yellow' =>
                '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />',
            'blue' =>
                '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />',
            'purple' =>
                '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />',
            'red' =>
                '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />',
        ];

        $stepColors = ['orange', 'blue', 'emerald'];
        $stepIcons = [
            '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />',
            '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />',
            '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />',
        ];

        $valueIcons = [
            '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />',
            '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />',
            '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />',
        ];
        $valueColors = ['orange', 'blue', 'emerald'];

        $whyIcons = [
            '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />',
            '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />',
            '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />',
            '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />',
        ];
        $whyColors = ['orange', 'emerald', 'blue', 'purple'];
    @endphp

    <div class="min-h-screen bg-gray-50">

        {{-- HERO --}}
        <section class="relative overflow-hidden bg-linear-to-br from-gray-900 via-gray-800 to-gray-900 text-white">
            <div class="absolute inset-0 opacity-10" aria-hidden="true">
                <div class="absolute -top-12 -right-12 w-48 h-48 sm:-top-24 sm:-right-24 sm:w-96 sm:h-96 rounded-full bg-[#F16A00] blur-3xl"></div>
                <div class="absolute bottom-0 left-0 w-40 h-40 sm:w-72 sm:h-72 rounded-full bg-[#FF8A1F] blur-3xl"></div>
            </div>

            <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 sm:py-24 lg:py-32">
                <div class="max-w-3xl">
                    <nav class="text-sm text-gray-400 mb-6" aria-label="Breadcrumb">
                        <a href="{{ route('home') }}" class="hover:text-white transition">Accueil</a>
                        <span class="mx-2">›</span>
                        <span class="text-[#FF8A1F]">À propos</span>
                    </nav>
                    <h1 class="text-3xl sm:text-5xl lg:text-6xl font-extrabold leading-tight tracking-tight">
                        {{ $hero['title'] ?? '' }}
                        <span
                            class="text-transparent bg-clip-text bg-linear-to-r from-[#FF8A1F] to-[#F16A00]">{{ $hero['highlight'] ?? '' }}</span>
                    </h1>
                    <p class="mt-6 text-lg sm:text-xl text-gray-300 leading-relaxed max-w-2xl">
                        {{ $hero['description'] ?? '' }}
                    </p>
                    <div class="mt-8 flex flex-wrap gap-3">
                        <a href="{{ route('residences.index') }}"
                            class="inline-flex items-center gap-2 px-6 py-3 bg-[#F16A00] hover:bg-[#CC5A00] text-white rounded-xl font-semibold transition-all shadow-lg shadow-none active:scale-95">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            {{ $hero['cta_primary'] ?? 'Explorer les résidences' }}
                        </a>
                        <a href="{{ route('pages.contact') }}"
                            class="inline-flex items-center gap-2 px-6 py-3 bg-white/10 hover:bg-white/20 text-white rounded-xl font-semibold transition-all border border-white/20 active:scale-95">
                            {{ $hero['cta_secondary'] ?? 'Nous contacter' }}
                        </a>
                    </div>
                </div>
            </div>
        </section>

        {{-- STATISTIQUES --}}
        <section class="relative -mt-8 sm:-mt-12 z-10">
            <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="bg-white rounded-2xl shadow-xl border border-gray-100 p-6 sm:p-8">
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-6 sm:gap-8">
                        <div class="text-center">
                            <div class="text-3xl sm:text-4xl font-extrabold text-[#F16A00]">
                                {{ number_format($stats['residences']) }}+</div>
                            <p class="mt-1 text-sm text-gray-500 font-medium">Résidences</p>
                        </div>
                        <div class="text-center">
                            <div class="text-3xl sm:text-4xl font-extrabold text-gray-900">
                                {{ number_format($stats['owners']) }}+</div>
                            <p class="mt-1 text-sm text-gray-500 font-medium">Propriétaires</p>
                        </div>
                        <div class="text-center">
                            <div class="text-3xl sm:text-4xl font-extrabold text-gray-900">
                                {{ number_format($stats['communes']) }}</div>
                            <p class="mt-1 text-sm text-gray-500 font-medium">Communes couvertes</p>
                        </div>
                        <div class="text-center">
                            <div class="text-3xl sm:text-4xl font-extrabold text-[#F16A00]">
                                {{ number_format($stats['reviews']) }}+</div>
                            <p class="mt-1 text-sm text-gray-500 font-medium">Avis vérifiés</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- NOTRE MISSION --}}
        <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 sm:py-24">
            <div class="grid lg:grid-cols-2 gap-12 lg:gap-16 items-center">
                <div>
                    <div
                        class="inline-flex items-center gap-2 bg-[#FFE7D1] text-[#A34700] px-4 py-1.5 rounded-full text-xs font-bold uppercase tracking-wider mb-6">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-8.707l-3-3a1 1 0 00-1.414 0l-3 3a1 1 0 001.414 1.414L9 9.414V13a1 1 0 102 0V9.414l1.293 1.293a1 1 0 001.414-1.414z"
                                clip-rule="evenodd" />
                        </svg>
                        {{ $mission['label'] ?? 'Notre Mission' }}
                    </div>
                    <h2 class="text-3xl sm:text-4xl font-extrabold text-gray-900 leading-tight mb-6">
                        {{ $mission['title'] ?? '' }}
                    </h2>
                    <div class="space-y-4 text-gray-600 leading-relaxed">
                        @foreach ($mission['paragraphs'] ?? [] as $paragraph)
                            <p>{!! $loop->index === 1
                                ? '<strong class="text-gray-900">' .
                                    e(Str::before($paragraph, '.')) .
                                    '.</strong> ' .
                                    e(Str::after($paragraph, '. '))
                                : e($paragraph) !!}</p>
                        @endforeach
                    </div>
                </div>

                {{-- Feature cards --}}
                <div class="grid grid-cols-2 gap-4">
                    @foreach ($mission['features'] ?? [] as $feature)
                        @php $color = $feature['color'] ?? 'orange'; @endphp
                        <div
                            class="bg-white rounded-2xl p-5 sm:p-6 shadow-sm border border-gray-100 hover:shadow-md hover:border-{{ $color }}-200 transition-all duration-300 group">
                            <div
                                class="w-12 h-12 bg-{{ $color }}-100 rounded-xl flex items-center justify-center mb-4 group-hover:bg-{{ $color }}-500 transition-colors">
                                <svg class="w-6 h-6 text-{{ $color }}-500 group-hover:text-white transition-colors"
                                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    {!! $featureIcons[$color] ?? $featureIcons['orange'] !!}
                                </svg>
                            </div>
                            <h3 class="font-bold text-gray-900 mb-1">{{ $feature['title'] }}</h3>
                            <p class="text-sm text-gray-500">{{ $feature['description'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- COMMENT ÇA MARCHE --}}
        <section class="bg-white py-16 sm:py-24">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-14">
                    <div
                        class="inline-flex items-center gap-2 bg-gray-100 text-gray-700 px-4 py-1.5 rounded-full text-xs font-bold uppercase tracking-wider mb-4">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z"
                                clip-rule="evenodd" />
                        </svg>
                        Comment ça marche
                    </div>
                    <h2 class="text-3xl sm:text-4xl font-extrabold text-gray-900">{{ $steps['title'] ?? '' }}</h2>
                    <p class="mt-3 text-gray-500 max-w-xl mx-auto">{{ $steps['subtitle'] ?? '' }}</p>
                </div>

                <div class="grid md:grid-cols-3 gap-8 lg:gap-12">
                    @foreach ($steps['items'] ?? [] as $i => $step)
                        @php $sColor = $stepColors[$i] ?? 'orange'; @endphp
                        <div class="relative text-center">
                            <div
                                class="absolute -top-3 left-1/2 -translate-x-1/2 w-8 h-8 bg-[#F16A00] rounded-full flex items-center justify-center text-white text-sm font-bold shadow-lg shadow-orange-200 z-10">
                                {{ $i + 1 }}</div>
                            <div
                                class="bg-{{ $sColor }}-50 rounded-2xl p-8 pt-10 hover:shadow-md transition-shadow">
                                <div
                                    class="w-16 h-16 bg-white rounded-2xl flex items-center justify-center mx-auto mb-5 shadow-sm">
                                    <svg class="w-8 h-8 text-{{ $sColor }}-500" fill="none"
                                        stroke="currentColor" viewBox="0 0 24 24">
                                        {!! $stepIcons[$i] ?? $stepIcons[0] !!}
                                    </svg>
                                </div>
                                <h3 class="text-lg font-bold text-gray-900 mb-2">{{ $step['title'] }}</h3>
                                <p class="text-gray-500 text-sm leading-relaxed">{{ $step['description'] }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- NOS VALEURS --}}
        <section class="py-16 sm:py-24">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-14">
                    <div
                        class="inline-flex items-center gap-2 bg-[#FFE7D1] text-[#A34700] px-4 py-1.5 rounded-full text-xs font-bold uppercase tracking-wider mb-4">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z"
                                clip-rule="evenodd" />
                        </svg>
                        Nos Valeurs
                    </div>
                    <h2 class="text-3xl sm:text-4xl font-extrabold text-gray-900">{{ $values['title'] ?? '' }}</h2>
                </div>

                <div class="grid md:grid-cols-3 gap-8">
                    @foreach ($values['items'] ?? [] as $i => $value)
                        @php $vColor = $valueColors[$i] ?? 'orange'; @endphp
                        <div
                            class="bg-white rounded-2xl p-8 shadow-sm border border-gray-100 hover:shadow-lg hover:-translate-y-1 transition-all duration-300">
                            <div
                                class="w-14 h-14 bg-{{ $vColor }}-100 rounded-2xl flex items-center justify-center mb-5">
                                <svg class="w-7 h-7 text-{{ $vColor }}-500" fill="none"
                                    stroke="currentColor" viewBox="0 0 24 24">
                                    {!! $valueIcons[$i] ?? $valueIcons[0] !!}
                                </svg>
                            </div>
                            <h3 class="text-xl font-bold text-gray-900 mb-3">{{ $value['title'] }}</h3>
                            <p class="text-gray-500 leading-relaxed">{{ $value['description'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- POURQUOI ReziApp --}}
        <section class="bg-white py-16 sm:py-24">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid lg:grid-cols-2 gap-12 lg:gap-16 items-center">
                    <div>
                        <div
                            class="inline-flex items-center gap-2 bg-emerald-100 text-emerald-700 px-4 py-1.5 rounded-full text-xs font-bold uppercase tracking-wider mb-6">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                    clip-rule="evenodd" />
                            </svg>
                            Pourquoi ReziApp
                        </div>
                        <h2 class="text-3xl sm:text-4xl font-extrabold text-gray-900 leading-tight mb-8">
                            {{ $why['title'] ?? '' }}
                        </h2>
                        <div class="space-y-5">
                            @foreach ($why['items'] ?? [] as $i => $item)
                                @php $wColor = $whyColors[$i] ?? 'orange'; @endphp
                                <div class="flex gap-4">
                                    <div
                                        class="shrink-0 w-10 h-10 bg-{{ $wColor }}-100 rounded-xl flex items-center justify-center">
                                        <svg class="w-5 h-5 text-{{ $wColor }}-500" fill="none"
                                            stroke="currentColor" viewBox="0 0 24 24">
                                            {!! $whyIcons[$i] ?? $whyIcons[0] !!}
                                        </svg>
                                    </div>
                                    <div>
                                        <h4 class="font-bold text-gray-900">{{ $item['title'] }}</h4>
                                        <p class="text-sm text-gray-500 mt-0.5">{{ $item['description'] }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- Illustration visuelle --}}
                    <div class="relative">
                        <div class="bg-linear-to-br from-[#FFF4EB] to-[#FFE7D1] rounded-3xl p-8 sm:p-12">
                            <div class="space-y-4">
                                <div class="bg-white rounded-2xl shadow-md p-4 flex gap-4 items-center">
                                    <div
                                        class="w-16 h-16 bg-[#FFD0A3] rounded-xl shrink-0 flex items-center justify-center">
                                        <svg class="w-8 h-8 text-[#F16A00]" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                                d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                                        </svg>
                                    </div>
                                    <div class="min-w-0">
                                        <div class="h-3 bg-gray-200 rounded-full w-32 mb-2"></div>
                                        <div class="h-2 bg-gray-100 rounded-full w-24 mb-2"></div>
                                        <div class="text-[#F16A00] font-bold text-sm">15 000 FCFA/jour</div>
                                    </div>
                                </div>
                                <div class="bg-white rounded-2xl shadow-md p-4 flex gap-4 items-center ml-4 sm:ml-8">
                                    <div
                                        class="w-16 h-16 bg-blue-200 rounded-xl shrink-0 flex items-center justify-center">
                                        <svg class="w-8 h-8 text-blue-500" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                                d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                        </svg>
                                    </div>
                                    <div class="min-w-0">
                                        <div class="h-3 bg-gray-200 rounded-full w-28 mb-2"></div>
                                        <div class="h-2 bg-gray-100 rounded-full w-20 mb-2"></div>
                                        <div class="text-blue-500 font-bold text-sm">25 000 FCFA/jour</div>
                                    </div>
                                </div>
                                <div class="bg-white rounded-2xl shadow-md p-4 flex gap-4 items-center">
                                    <div
                                        class="w-16 h-16 bg-emerald-200 rounded-xl shrink-0 flex items-center justify-center">
                                        <svg class="w-8 h-8 text-emerald-500" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                                d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                                        </svg>
                                    </div>
                                    <div class="min-w-0">
                                        <div class="h-3 bg-gray-200 rounded-full w-36 mb-2"></div>
                                        <div class="h-2 bg-gray-100 rounded-full w-28 mb-2"></div>
                                        <div class="text-emerald-500 font-bold text-sm">8 500 FCFA/jour</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div
                            class="absolute -bottom-4 -left-4 bg-white rounded-2xl shadow-lg border border-gray-100 p-4 flex items-center gap-3">
                            <div class="w-10 h-10 bg-emerald-100 rounded-full flex items-center justify-center">
                                <svg class="w-5 h-5 text-emerald-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                        clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-bold text-gray-900">Gratuit pour les locataires</p>
                                <p class="text-xs text-gray-500">Recherche sans frais</p>
                            </div>
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
                    <div class="absolute top-0 right-0 w-64 h-64 bg-[#F16A00]/10 rounded-full blur-3xl"
                        aria-hidden="true"></div>
                    <div class="absolute bottom-0 left-0 w-48 h-48 bg-[#F16A00]/10 rounded-full blur-3xl"
                        aria-hidden="true"></div>

                    <div class="relative">
                        <h2 class="text-2xl sm:text-4xl font-extrabold mb-4">{{ $cta['title'] ?? '' }}</h2>
                        <p class="text-gray-300 mb-8 max-w-xl mx-auto text-lg">{{ $cta['description'] ?? '' }}</p>
                        <div class="flex flex-col sm:flex-row gap-4 justify-center">
                            <a href="{{ route('residences.index') }}"
                                class="inline-flex items-center justify-center gap-2 px-8 py-3.5 bg-[#F16A00] hover:bg-[#CC5A00] text-white rounded-xl font-semibold transition-all shadow-lg shadow-none active:scale-95">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                                {{ $cta['cta_primary'] ?? 'Chercher une résidence' }}
                            </a>
                            <a href="{{ route('register') }}"
                                class="inline-flex items-center justify-center gap-2 px-8 py-3.5 bg-white/10 hover:bg-white/20 text-white rounded-xl font-semibold transition-all border border-white/20 active:scale-95">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                                </svg>
                                {{ $cta['cta_secondary'] ?? 'Créer un compte gratuit' }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
</x-app-layout>
