@extends('layouts.app')

@section('title', $user->name . ' - Profil')

@section('content')
    <div class="min-h-screen bg-linear-to-br from-gray-50 via-white to-[#FFF4EB]/30">
        <!-- Hero Header -->
        <div class="relative">
            <!-- Fond avec motif -->
            <div class="absolute inset-0 bg-linear-to-r from-[#F16A00] via-orange-400 to-amber-500 h-64 sm:h-80">
                <div class="absolute inset-0 opacity-10">
                    <svg class="w-full h-full" viewBox="0 0 100 100" preserveAspectRatio="none">
                        <defs>
                            <pattern id="grid" width="10" height="10" patternUnits="userSpaceOnUse">
                                <path d="M 10 0 L 0 0 0 10" fill="none" stroke="white" stroke-width="0.5" />
                            </pattern>
                        </defs>
                        <rect width="100" height="100" fill="url(#grid)" />
                    </svg>
                </div>
                <!-- Cercles décoratifs -->
                <div class="absolute top-10 left-10 w-32 h-32 bg-white/10 rounded-full blur-2xl"></div>
                <div class="absolute bottom-10 right-20 w-48 h-48 bg-amber-300/20 rounded-full blur-3xl"></div>
            </div>

            <!-- Contenu du header -->
            <div class="relative max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 pt-12 pb-24 sm:pt-16 sm:pb-32">
                <!-- Breadcrumb -->
                <nav class="mb-6">
                    <ol class="flex items-center gap-2 text-sm text-white/80">
                        <li><a href="{{ route('home') }}" class="hover:text-white transition">Accueil</a></li>
                        <li><svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z"
                                    clip-rule="evenodd" />
                            </svg></li>
                        <li class="text-white font-medium">Profil</li>
                    </ol>
                </nav>

                <div class="flex flex-col sm:flex-row items-center sm:items-end gap-6">
                    <!-- Avatar amélioré -->
                    <div class="relative group">
                        <div class="absolute -inset-2 bg-white/20 rounded-full blur-lg group-hover:bg-white/30 transition">
                        </div>
                        <img loading="lazy" src="{{ $user->getAvatarUrl() }}" alt="{{ $user->name }}"
                            class="relative w-36 h-36 sm:w-44 sm:h-44 rounded-full border-4 border-white shadow-2xl object-cover ring-4 ring-white/30">

                        @if ($profile->is_superhost)
                            <div class="absolute -bottom-2 -right-2 bg-linear-to-br from-yellow-400 to-amber-500 text-white p-3 rounded-full shadow-lg ring-4 ring-white"
                                title="Superhost">
                                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                                    <path
                                        d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                </svg>
                            </div>
                        @endif

                        @if ($user->email_verified_at)
                            <div class="absolute -bottom-2 left-0 bg-green-500 text-white p-2 rounded-full shadow-lg ring-2 ring-white"
                                title="Compte vérifié">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                        clip-rule="evenodd" />
                                </svg>
                            </div>
                        @endif
                    </div>

                    <!-- Infos principales -->
                    <div class="flex-1 text-center sm:text-left text-white">
                        <h1 class="text-3xl sm:text-4xl font-bold mb-2 drop-shadow-sm">{{ $user->name }}</h1>

                        <div class="flex flex-wrap items-center justify-center sm:justify-start gap-3 text-white/90 mb-4">
                            @if ($profile->location)
                                <span class="flex items-center gap-1.5 text-sm">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                    </svg>
                                    {{ $profile->location }}
                                </span>
                            @endif

                            <span class="flex items-center gap-1.5 text-sm">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                {{ $profile->member_since_formatted }}
                            </span>

                            @if ($user->isOwner())
                                <span
                                    class="inline-flex items-center gap-1.5 px-3 py-1 bg-white/20 backdrop-blur-sm rounded-full text-sm font-medium">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path
                                            d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z" />
                                    </svg>
                                    Propriétaire
                                </span>
                            @endif
                        </div>

                        <!-- Actions -->
                        <div class="flex flex-wrap items-center justify-center sm:justify-start gap-3">
                            @auth
                                @if (Auth::id() === $user->id)
                                    <a href="{{ route('profile.edit') }}"
                                        class="inline-flex items-center gap-2 px-5 py-2.5 bg-white text-[#CC5A00] hover:bg-[#FFF4EB] rounded-xl font-medium shadow-lg shadow-orange-900/20 transition-all hover:scale-105">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                        Modifier mon profil
                                    </a>
                                    <a href="{{ route('profile.public.edit') }}"
                                        class="inline-flex items-center gap-2 px-5 py-2.5 bg-white/20 backdrop-blur-sm text-white hover:bg-white/30 rounded-xl font-medium transition-all">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                        Profil public
                                    </a>
                                @else
                                    @if (isset($residences) && $residences->count() > 0)
                                        {{-- Owner with residences: show contact form --}}
                                        <div x-data="{ showContactModal: false, selectedResidence: {{ $residences->count() === 1 ? $residences->first()->id : 'null' }} }" class="inline-block">
                                            @if ($residences->count() === 1)
                                                {{-- Single residence: direct POST form --}}
                                                <form action="{{ route('chat.start') }}" method="POST">
                                                    @csrf
                                                    <input type="hidden" name="residence_id" value="{{ $residences->first()->id }}">
                                                    <button type="submit"
                                                        class="inline-flex items-center gap-2 px-6 py-3 bg-white text-[#CC5A00] hover:bg-[#FFF4EB] rounded-xl font-semibold shadow-lg shadow-orange-900/20 transition-all hover:scale-105">
                                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                                d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                                        </svg>
                                                        Contacter {{ $user->first_name ?? $user->name }}
                                                    </button>
                                                </form>
                                            @else
                                                {{-- Multiple residences: show picker modal --}}
                                                <button @click="showContactModal = true"
                                                    class="inline-flex items-center gap-2 px-6 py-3 bg-white text-[#CC5A00] hover:bg-[#FFF4EB] rounded-xl font-semibold shadow-lg shadow-orange-900/20 transition-all hover:scale-105">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                            d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                                    </svg>
                                                    Contacter {{ $user->first_name ?? $user->name }}
                                                </button>

                                                {{-- Residence picker modal --}}
                                                <div x-show="showContactModal" x-cloak
                                                    class="fixed inset-0 z-50 flex items-center justify-center p-4"
                                                    @keydown.escape.window="showContactModal = false">
                                                    <div class="fixed inset-0 bg-black/50" @click="showContactModal = false"></div>
                                                    <div class="relative bg-white rounded-2xl shadow-xl max-w-md w-full overflow-hidden"
                                                        x-transition:enter="transition ease-out duration-200"
                                                        x-transition:enter-start="opacity-0 scale-95"
                                                        x-transition:enter-end="opacity-100 scale-100">
                                                        <div class="px-5 py-4 border-b border-gray-100">
                                                            <h3 class="text-base font-bold text-gray-900">Choisir une résidence</h3>
                                                            <p class="text-sm text-gray-500 mt-1">À propos de quelle résidence souhaitez-vous contacter {{ $user->first_name ?? $user->name }} ?</p>
                                                        </div>
                                                        <form action="{{ route('chat.start') }}" method="POST">
                                                            @csrf
                                                            <input type="hidden" name="residence_id" :value="selectedResidence">
                                                            <div class="px-5 py-3 max-h-64 overflow-y-auto divide-y divide-gray-50">
                                                                @foreach ($residences as $residence)
                                                                    <label class="flex items-center gap-3 px-3 py-3 cursor-pointer rounded-lg transition-colors"
                                                                        :class="selectedResidence == {{ $residence->id }} ? 'bg-[#FFF4EB]' : 'hover:bg-gray-50'">
                                                                        <input type="radio" name="residence_radio" value="{{ $residence->id }}"
                                                                            @click="selectedResidence = {{ $residence->id }}"
                                                                            :checked="selectedResidence == {{ $residence->id }}"
                                                                            class="text-[#F16A00] focus:ring-[#F16A00]/30 border-gray-300">
                                                                        <div class="flex-1 min-w-0">
                                                                            <p class="text-sm font-medium text-gray-800 truncate">{{ $residence->name ?? $residence->title }}</p>
                                                                            @if ($residence->commune)
                                                                                <p class="text-xs text-gray-400">{{ $residence->commune }}</p>
                                                                            @endif
                                                                        </div>
                                                                    </label>
                                                                @endforeach
                                                            </div>
                                                            <div class="flex items-center justify-end gap-2 px-5 py-4 border-t border-gray-100 bg-gray-50/50">
                                                                <button type="button" @click="showContactModal = false"
                                                                    class="px-4 py-2 text-sm font-medium text-gray-600 hover:text-gray-800 hover:bg-gray-100 rounded-xl transition-colors">
                                                                    Annuler
                                                                </button>
                                                                <button type="submit" :disabled="!selectedResidence"
                                                                    class="px-5 py-2 text-sm font-semibold rounded-xl transition-all"
                                                                    :class="selectedResidence ? 'bg-[#F16A00] text-white hover:bg-[#CC5A00] shadow-sm' : 'bg-gray-200 text-gray-400 cursor-not-allowed'">
                                                                    Contacter
                                                                </button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    @else
                                        {{-- Non-owner user: redirect to chat index --}}
                                        <a href="{{ route('chat.index') }}"
                                            class="inline-flex items-center gap-2 px-6 py-3 bg-white text-[#CC5A00] hover:bg-[#FFF4EB] rounded-xl font-semibold shadow-lg shadow-orange-900/20 transition-all hover:scale-105">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                            </svg>
                                            Contacter {{ $user->first_name ?? $user->name }}
                                        </a>
                                    @endif
                                    <button
                                        onclick="navigator.share ? navigator.share({title: '{{ $user->name }} sur REZI', url: window.location.href}) : navigator.clipboard.writeText(window.location.href).then(() => alert('Lien copié!'))"
                                        class="inline-flex items-center gap-2 px-4 py-3 bg-white/20 backdrop-blur-sm text-white hover:bg-white/30 rounded-xl font-medium transition-all">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z" />
                                        </svg>
                                        Partager
                                    </button>
                                @endif
                            @else
                                <a href="{{ route('login') }}"
                                    class="inline-flex items-center gap-2 px-6 py-3 bg-white text-[#CC5A00] hover:bg-[#FFF4EB] rounded-xl font-semibold shadow-lg transition-all hover:scale-105">
                                    Connectez-vous pour contacter
                                </a>
                            @endauth
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Contenu principal -->
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 -mt-16 pb-12 relative z-10">

            <!-- Statistiques rapides (pour propriétaires) -->
            @if ($user->isOwner())
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-8">
                    @isset($averageRating)
                        <div
                            class="bg-white rounded-2xl shadow-lg shadow-gray-200/50 p-5 text-center hover:shadow-xl transition-shadow">
                            <div class="flex items-center justify-center gap-1 mb-2">
                                <svg class="w-6 h-6 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path
                                        d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                </svg>
                                <span class="text-2xl font-bold text-gray-900">{{ $averageRating }}</span>
                            </div>
                            <p class="text-sm text-gray-500">Note moyenne</p>
                        </div>
                    @endisset

                    <div
                        class="bg-white rounded-2xl shadow-lg shadow-gray-200/50 p-5 text-center hover:shadow-xl transition-shadow">
                        <div class="text-2xl font-bold text-gray-900 mb-2">{{ $totalReviews ?? 0 }}</div>
                        <p class="text-sm text-gray-500">Avis reçus</p>
                    </div>

                    <div
                        class="bg-white rounded-2xl shadow-lg shadow-gray-200/50 p-5 text-center hover:shadow-xl transition-shadow">
                        <div class="text-2xl font-bold text-gray-900 mb-2">{{ $user->approvedResidencesCount() }}</div>
                        <p class="text-sm text-gray-500">Résidences</p>
                    </div>

                    <div
                        class="bg-white rounded-2xl shadow-lg shadow-gray-200/50 p-5 text-center hover:shadow-xl transition-shadow">
                        <div class="text-2xl font-bold text-[#F16A00] mb-2">{{ $profile->response_rate_formatted }}</div>
                        <p class="text-sm text-gray-500">Taux de réponse</p>
                    </div>
                </div>
            @endif

            <!-- Badges -->
            @if ($badges->count() > 0)
                <div class="bg-white rounded-2xl shadow-lg shadow-gray-200/50 p-6 mb-8">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                            <svg class="w-5 h-5 text-[#F16A00]" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                    clip-rule="evenodd" />
                            </svg>
                            Badges & Distinctions
                        </h2>
                        <a href="{{ route('profile.badges', $user) }}"
                            class="text-[#F16A00] hover:text-[#CC5A00] text-sm font-medium flex items-center gap-1">
                            Voir tout
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </a>
                    </div>

                    <div class="flex flex-wrap gap-3">
                        @foreach ($badges as $badge)
                            @php
                                $badgeStyles = [
                                    'gold' =>
                                        'bg-linear-to-br from-yellow-100 to-amber-100 text-yellow-800 border-yellow-200 shadow-yellow-100',
                                    'blue' =>
                                        'bg-linear-to-br from-blue-50 to-indigo-100 text-blue-800 border-blue-200 shadow-blue-100',
                                    'green' =>
                                        'bg-linear-to-br from-green-50 to-emerald-100 text-green-800 border-green-200 shadow-green-100',
                                    'purple' =>
                                        'bg-linear-to-br from-purple-50 to-violet-100 text-purple-800 border-purple-200 shadow-purple-100',
                                    'orange' =>
                                        'bg-linear-to-br from-[#FFF4EB] to-amber-100 text-[#8e0730] border-[#FFD0A3] shadow-orange-100',
                                    'teal' =>
                                        'bg-linear-to-br from-teal-50 to-cyan-100 text-teal-800 border-teal-200 shadow-teal-100',
                                ];
                            @endphp
                            <div class="group relative">
                                <span
                                    class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold border shadow-sm {{ $badgeStyles[$badge->color] ?? 'bg-gray-50 text-gray-800 border-gray-200' }} transition-all hover:scale-105 cursor-help">
                                    @if ($badge->icon === 'star')
                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                            <path
                                                d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                        </svg>
                                    @elseif($badge->icon === 'bolt')
                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd"
                                                d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z"
                                                clip-rule="evenodd" />
                                        </svg>
                                    @elseif($badge->icon === 'check-badge')
                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd"
                                                d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                                clip-rule="evenodd" />
                                        </svg>
                                    @else
                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd"
                                                d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                                clip-rule="evenodd" />
                                        </svg>
                                    @endif
                                    {{ $badge->name }}
                                </span>
                                <!-- Tooltip -->
                                <div
                                    class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 px-3 py-2 bg-gray-900 text-white text-xs rounded-lg opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all whitespace-nowrap z-10">
                                    {{ $badge->description }}
                                    <div
                                        class="absolute top-full left-1/2 -translate-x-1/2 border-4 border-transparent border-t-gray-900">
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Colonne gauche : Infos -->
                <div class="space-y-6">
                    <!-- À propos -->
                    <div class="bg-white rounded-2xl shadow-lg shadow-gray-200/50 p-6 hover:shadow-xl transition-shadow">
                        <h2 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
                            <div class="p-2 bg-[#FFE7D1] rounded-lg">
                                <svg class="w-5 h-5 text-[#CC5A00]" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                            </div>
                            À propos
                        </h2>

                        @if ($profile->bio)
                            <div class="bg-gray-50 rounded-xl p-4 mb-5">
                                <p class="text-gray-700 leading-relaxed italic">"{{ $profile->bio }}"</p>
                            </div>
                        @endif

                        <div class="space-y-4">
                            @if ($profile->location)
                                <div
                                    class="flex items-center gap-3 p-3 bg-gray-50 rounded-xl hover:bg-gray-100 transition">
                                    <div class="p-2 bg-white rounded-lg shadow-sm">
                                        <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-xs text-gray-500 uppercase tracking-wide">Localisation</p>
                                        <p class="text-gray-900 font-medium">{{ $profile->location }}</p>
                                    </div>
                                </div>
                            @endif

                            @if ($profile->work)
                                <div
                                    class="flex items-center gap-3 p-3 bg-gray-50 rounded-xl hover:bg-gray-100 transition">
                                    <div class="p-2 bg-white rounded-lg shadow-sm">
                                        <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-xs text-gray-500 uppercase tracking-wide">Profession</p>
                                        <p class="text-gray-900 font-medium">{{ $profile->work }}</p>
                                    </div>
                                </div>
                            @endif

                            @if ($profile->languages && count($profile->languages) > 0)
                                <div
                                    class="flex items-center gap-3 p-3 bg-gray-50 rounded-xl hover:bg-gray-100 transition">
                                    <div class="p-2 bg-white rounded-lg shadow-sm">
                                        <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129" />
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-xs text-gray-500 uppercase tracking-wide">Langues parlées</p>
                                        <p class="text-gray-900 font-medium">{{ $profile->languages_formatted }}</p>
                                    </div>
                                </div>
                            @endif

                            @if ($profile->show_email && $user->email)
                                <a href="mailto:{{ $user->email }}"
                                    class="flex items-center gap-3 p-3 bg-[#FFF4EB] rounded-xl hover:bg-[#FFE7D1] transition group">
                                    <div class="p-2 bg-white rounded-lg shadow-sm">
                                        <svg class="w-5 h-5 text-[#F16A00]" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-xs text-gray-500 uppercase tracking-wide">Email</p>
                                        <p class="text-[#CC5A00] font-medium group-hover:underline">{{ $user->email }}
                                        </p>
                                    </div>
                                </a>
                            @endif

                            @if ($profile->show_phone && $user->phone)
                                <a href="tel:{{ $user->phone }}"
                                    class="flex items-center gap-3 p-3 bg-green-50 rounded-xl hover:bg-green-100 transition group">
                                    <div class="p-2 bg-white rounded-lg shadow-sm">
                                        <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-xs text-gray-500 uppercase tracking-wide">Téléphone</p>
                                        <p class="text-green-600 font-medium group-hover:underline">{{ $user->phone }}
                                        </p>
                                    </div>
                                </a>
                            @endif
                        </div>
                    </div>

                    <!-- Réactivité (pour propriétaires) -->
                    @if ($user->isOwner())
                        <div
                            class="bg-white rounded-2xl shadow-lg shadow-gray-200/50 p-6 hover:shadow-xl transition-shadow">
                            <h2 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
                                <div class="p-2 bg-green-100 rounded-lg">
                                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M13 10V3L4 14h7v7l9-11h-7z" />
                                    </svg>
                                </div>
                                Réactivité
                            </h2>

                            <div class="space-y-5">
                                <!-- Taux de réponse -->
                                <div>
                                    <div class="flex justify-between text-sm mb-2">
                                        <span class="text-gray-600">Taux de réponse</span>
                                        <span
                                            class="font-bold text-gray-900">{{ $profile->response_rate_formatted }}</span>
                                    </div>
                                    <div class="w-full bg-gray-100 rounded-full h-3 overflow-hidden">
                                        <div class="h-full rounded-full transition-all duration-500 {{ $profile->response_rate >= 90 ? 'bg-linear-to-r from-green-400 to-emerald-500' : ($profile->response_rate >= 70 ? 'bg-linear-to-r from-yellow-400 to-[#F16A00]' : 'bg-linear-to-r from-red-400 to-red-500') }}"
                                            style="width: {{ min(100, $profile->response_rate) }}%"></div>
                                    </div>
                                    @if ($profile->response_rate >= 90)
                                        <p class="text-xs text-green-600 mt-1 flex items-center gap-1">
                                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd"
                                                    d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                                    clip-rule="evenodd" />
                                            </svg>
                                            Excellent taux de réponse
                                        </p>
                                    @endif
                                </div>

                                <!-- Temps de réponse -->
                                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-xl">
                                    <div class="flex items-center gap-3">
                                        <div class="p-2 bg-white rounded-lg shadow-sm">
                                            <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        </div>
                                        <span class="text-gray-600">Temps de réponse</span>
                                    </div>
                                    <span class="font-bold text-gray-900">{{ $profile->response_time_description }}</span>
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Vérifications -->
                    <div class="bg-white rounded-2xl shadow-lg shadow-gray-200/50 p-6 hover:shadow-xl transition-shadow">
                        <h2 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
                            <div class="p-2 bg-blue-100 rounded-lg">
                                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                </svg>
                            </div>
                            Vérifications
                        </h2>

                        <div class="space-y-3">
                            <div
                                class="flex items-center gap-3 p-3 rounded-xl {{ $user->email_verified_at ? 'bg-green-50' : 'bg-gray-50' }}">
                                @if ($user->email_verified_at)
                                    <div class="p-2 bg-green-500 rounded-full">
                                        <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd"
                                                d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                                clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                    <span class="text-green-800 font-medium">Email vérifié</span>
                                @else
                                    <div class="p-2 bg-gray-300 rounded-full">
                                        <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd"
                                                d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                                                clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                    <span class="text-gray-500">Email non vérifié</span>
                                @endif
                            </div>

                            <div
                                class="flex items-center gap-3 p-3 rounded-xl {{ $user->hasBadge(\App\Models\Badge::TYPE_VERIFIED) ? 'bg-blue-50' : 'bg-gray-50' }}">
                                @if ($user->hasBadge(\App\Models\Badge::TYPE_VERIFIED))
                                    <div class="p-2 bg-blue-500 rounded-full">
                                        <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd"
                                                d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                                clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                    <span class="text-blue-800 font-medium">Identité vérifiée</span>
                                @else
                                    <div class="p-2 bg-gray-300 rounded-full">
                                        <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd"
                                                d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                                                clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                    <span class="text-gray-500">Identité non vérifiée</span>
                                @endif
                            </div>

                            @if ($user->phone)
                                <div class="flex items-center gap-3 p-3 rounded-xl bg-green-50">
                                    <div class="p-2 bg-green-500 rounded-full">
                                        <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd"
                                                d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                                clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                    <span class="text-green-800 font-medium">Téléphone vérifié</span>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Colonne droite : Contenu principal -->
                <div class="lg:col-span-2 space-y-6">
                    @if ($user->isOwner())
                        <!-- Résidences du propriétaire -->
                        @if (isset($residences) && $residences->count() > 0)
                            <div
                                class="bg-white rounded-2xl shadow-lg shadow-gray-200/50 p-6 hover:shadow-xl transition-shadow">
                                <div class="flex items-center justify-between mb-6">
                                    <h2 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                                        <div class="p-2 bg-[#FFE7D1] rounded-lg">
                                            <svg class="w-5 h-5 text-[#CC5A00]" fill="currentColor" viewBox="0 0 20 20">
                                                <path
                                                    d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z" />
                                            </svg>
                                        </div>
                                        Résidences de {{ $user->first_name ?? $user->name }}
                                        <span
                                            class="ml-2 text-sm font-normal text-gray-500">({{ $user->approvedResidencesCount() }})</span>
                                    </h2>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                                    @foreach ($residences as $residence)
                                        <a href="{{ route('residences.show', $residence) }}" class="group block">
                                            <div class="relative aspect-4/3 rounded-xl overflow-hidden mb-3 shadow-md">
                                                @if ($residence->photos->count() > 0)
                                                    <img loading="lazy"
                                                        src="{{ storage_url($residence->photos->first()?->path) }}"
                                                        alt="{{ $residence->title }}"
                                                        class="w-full h-full object-cover group-hover:scale-110 transition duration-500">
                                                @else
                                                    <div
                                                        class="w-full h-full bg-linear-to-br from-gray-100 to-gray-200 flex items-center justify-center">
                                                        <svg class="w-16 h-16 text-gray-300" fill="none"
                                                            stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                        </svg>
                                                    </div>
                                                @endif
                                                <!-- Overlay gradient -->
                                                <div
                                                    class="absolute inset-0 bg-linear-to-t from-black/50 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity">
                                                </div>

                                                <!-- Prix badge -->
                                                @if ($residence->price)
                                                    <div
                                                        class="absolute top-3 right-3 bg-white/95 backdrop-blur-sm px-3 py-1.5 rounded-full shadow-lg">
                                                        <span
                                                            class="font-bold text-gray-900">{{ number_format($residence->price, 0, ',', ' ') }}</span>
                                                        <span class="text-gray-500 text-sm">FCFA/jour</span>
                                                    </div>
                                                @endif
                                            </div>

                                            <h3
                                                class="font-semibold text-gray-900 group-hover:text-[#F16A00] transition mb-1">
                                                {{ $residence->title }}</h3>

                                            <div class="flex items-center gap-2 text-sm text-gray-500">
                                                @if ($residence->reviews->count() > 0)
                                                    <span class="flex items-center gap-1 font-medium">
                                                        <svg class="w-4 h-4 text-yellow-400" fill="currentColor"
                                                            viewBox="0 0 20 20">
                                                            <path
                                                                d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                                        </svg>
                                                        {{ number_format($residence->reviews->avg('rating'), 1) }}
                                                    </span>
                                                    <span class="text-gray-300">•</span>
                                                @endif
                                                <span class="flex items-center gap-1">
                                                    <svg class="w-4 h-4 text-gray-400" fill="none"
                                                        stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                                    </svg>
                                                    {{ $residence->commune }}
                                                </span>
                                            </div>
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        @else
                            <!-- État vide pour les résidences -->
                            <div class="bg-white rounded-2xl shadow-lg shadow-gray-200/50 p-12 text-center">
                                <div
                                    class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                    <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                                    </svg>
                                </div>
                                <h3 class="text-lg font-semibold text-gray-900 mb-2">Aucune résidence</h3>
                                <p class="text-gray-500">Ce propriétaire n'a pas encore publié de résidence.</p>
                            </div>
                        @endif

                        <!-- Avis reçus -->
                        @if (isset($receivedReviews) && $receivedReviews->count() > 0)
                            <div
                                class="bg-white rounded-2xl shadow-lg shadow-gray-200/50 p-6 hover:shadow-xl transition-shadow">
                                <div class="flex items-center justify-between mb-6">
                                    <h2 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                                        <div class="p-2 bg-yellow-100 rounded-lg">
                                            <svg class="w-5 h-5 text-yellow-600" fill="currentColor" viewBox="0 0 20 20">
                                                <path
                                                    d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                            </svg>
                                        </div>
                                        Avis des voyageurs
                                        <span class="ml-2 text-sm font-normal text-gray-500">({{ $totalReviews }})</span>
                                    </h2>
                                    <a href="{{ route('profile.received-reviews', $user) }}"
                                        class="text-[#F16A00] hover:text-[#CC5A00] text-sm font-medium flex items-center gap-1">
                                        Voir tout
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 5l7 7-7 7" />
                                        </svg>
                                    </a>
                                </div>

                                <div class="space-y-6">
                                    @foreach ($receivedReviews as $review)
                                        @include('reviews.partials.review-card', ['review' => $review])
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    @else
                        <!-- Avis donnés (pour utilisateurs) -->
                        @if (isset($givenReviews) && $givenReviews->count() > 0)
                            <div
                                class="bg-white rounded-2xl shadow-lg shadow-gray-200/50 p-6 hover:shadow-xl transition-shadow">
                                <div class="flex items-center justify-between mb-6">
                                    <h2 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                                        <div class="p-2 bg-purple-100 rounded-lg">
                                            <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" />
                                            </svg>
                                        </div>
                                        Avis laissés par {{ $user->first_name ?? $user->name }}
                                        <span
                                            class="ml-2 text-sm font-normal text-gray-500">({{ $totalGivenReviews }})</span>
                                    </h2>
                                    <a href="{{ route('profile.given-reviews', $user) }}"
                                        class="text-[#F16A00] hover:text-[#CC5A00] text-sm font-medium flex items-center gap-1">
                                        Voir tout
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 5l7 7-7 7" />
                                        </svg>
                                    </a>
                                </div>

                                <div class="space-y-6">
                                    @foreach ($givenReviews as $review)
                                        @include('reviews.partials.review-card', [
                                            'review' => $review,
                                            'showResidence' => true,
                                        ])
                                    @endforeach
                                </div>
                            </div>
                        @else
                            <!-- État vide pour les avis -->
                            <div class="bg-white rounded-2xl shadow-lg shadow-gray-200/50 p-12 text-center">
                                <div
                                    class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                    <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                    </svg>
                                </div>
                                <h3 class="text-lg font-semibold text-gray-900 mb-2">Pas encore d'avis</h3>
                                <p class="text-gray-500">{{ $user->first_name ?? $user->name }} n'a pas encore laissé
                                    d'avis sur les résidences visitées.</p>
                            </div>
                        @endif
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
