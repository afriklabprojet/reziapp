@php
    $isOwner = auth()->user()?->isOwner();
@endphp

@extends($isOwner ? 'layouts.owner' : 'layouts.client', $isOwner ? [] : ['sidebarActive' => 'profile'])

@section('title', 'Mon Profil - Rezi Studio Meublé Faya')

@section($isOwner ? 'owner-content' : 'client-content')
    {{-- En-tête du profil --}}
    <div
        class="bg-linear-to-r from-[#F16A00] to-[#CC5A00] rounded-2xl p-6 sm:p-8 mb-6 text-white relative overflow-hidden">
        {{-- Motif décoratif --}}
        <div class="absolute top-0 right-0 w-64 h-64 bg-white/10 rounded-full -translate-y-1/2 translate-x-1/2"></div>
        <div class="absolute bottom-0 left-0 w-32 h-32 bg-white/10 rounded-full translate-y-1/2 -translate-x-1/2"></div>

        <div class="relative flex flex-col sm:flex-row items-center gap-6">
            {{-- Photo de profil --}}
            <form method="post" action="{{ route('profile.update') }}" enctype="multipart/form-data" id="photo-form">
                @csrf
                @method('patch')
                <input type="hidden" name="photo_only" value="1">
                <input type="hidden" name="name" value="{{ $user->name }}">
                <input type="hidden" name="email" value="{{ $user->email }}">
                <div class="relative group">
                    <div class="w-24 h-24 sm:w-28 sm:h-28 rounded-full overflow-hidden bg-white/20 ring-4 ring-white/30">
                        @if ($user->profile_photo || $user->avatar)
                            <img src="{{ $user->getAvatarUrl() }}" alt="{{ $user->name }}"
                                class="w-full h-full object-cover">
                        @else
                            <div class="w-full h-full flex items-center justify-center">
                                <span
                                    class="text-4xl font-bold text-white">{{ strtoupper(substr($user->name, 0, 1)) }}</span>
                            </div>
                        @endif
                    </div>
                    <label for="profile_photo"
                        class="absolute inset-0 flex items-center justify-center bg-black/50 rounded-full opacity-0 group-hover:opacity-100 cursor-pointer transition-opacity">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    </label>
                    <input type="file" id="profile_photo" name="profile_photo" class="hidden" accept="image/*"
                        onchange="document.getElementById('photo-form').submit()">
                </div>
                @error('profile_photo')
                    <p class="text-xs text-red-200 mt-2 text-center">{{ $message }}</p>
                @enderror
            </form>

            {{-- Infos utilisateur --}}
            <div class="text-center sm:text-left flex-1">
                <h1 class="text-2xl sm:text-3xl font-bold">{{ $user->name }}</h1>
                <p class="text-white/80 mt-1">{{ $user->email }}</p>
                <div class="flex flex-wrap items-center justify-center sm:justify-start gap-3 mt-3">
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-white/20">
                        @if ($user->role === 'owner')
                            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                            </svg>
                            Propriétaire
                        @else
                            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                            Voyageur
                        @endif
                    </span>
                    @if ($user->email_verified_at)
                        <span
                            class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-500/30 text-green-100">
                            <svg class="w-4 h-4 mr-1.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                    clip-rule="evenodd" />
                            </svg>
                            Email vérifié
                        </span>
                    @endif
                    <span class="text-white/70 text-sm">
                        Membre depuis {{ $user->created_at->translatedFormat('F Y') }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    {{-- Statistiques rapides --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 sm:gap-4 mb-6">
        <div class="bg-white rounded-xl border border-gray-100 p-3 sm:p-4 text-center">
            <div class="w-10 h-10 mx-auto bg-blue-100 rounded-lg flex items-center justify-center mb-2">
                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
            </div>
            <p class="text-2xl font-bold text-gray-900">{{ $stats['total_bookings'] }}</p>
            <p class="text-sm text-gray-500">Réservations</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-100 p-3 sm:p-4 text-center">
            <div class="w-10 h-10 mx-auto bg-red-100 rounded-lg flex items-center justify-center mb-2">
                <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                </svg>
            </div>
            <p class="text-2xl font-bold text-gray-900">{{ $stats['total_favorites'] }}</p>
            <p class="text-sm text-gray-500">Favoris</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-100 p-3 sm:p-4 text-center">
            <div class="w-10 h-10 mx-auto bg-yellow-100 rounded-lg flex items-center justify-center mb-2">
                <svg class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                </svg>
            </div>
            <p class="text-2xl font-bold text-gray-900">{{ $stats['total_reviews'] }}</p>
            <p class="text-sm text-gray-500">Avis laissés</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-100 p-3 sm:p-4 text-center">
            <div class="w-10 h-10 mx-auto bg-green-100 rounded-lg flex items-center justify-center mb-2">
                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                </svg>
            </div>
            <p class="text-2xl font-bold text-gray-900">{{ $stats['total_conversations'] }}</p>
            <p class="text-sm text-gray-500">Conversations</p>
        </div>
    </div>

    {{-- Sections du profil --}}
    <div class="grid lg:grid-cols-2 gap-6">
        {{-- Informations personnelles --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
                <h2 class="text-lg font-semibold text-gray-900 flex items-center">
                    <svg class="w-5 h-5 mr-2 text-[#F16A00]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                    Informations personnelles
                </h2>
            </div>
            <form method="post" action="{{ route('profile.update') }}" class="p-6 space-y-5">
                @csrf
                @method('patch')

                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Nom complet</label>
                    <input type="text" id="name" name="name" value="{{ old('name', $user->name) }}" required
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#F16A00] focus:border-[#F16A00] transition">
                    @error('name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Adresse email</label>
                    <input type="email" id="email" name="email" value="{{ old('email', $user->email) }}"
                        required
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#F16A00] focus:border-[#F16A00] transition">
                    @error('email')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror

                    @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && !$user->hasVerifiedEmail())
                        <div class="mt-2 p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
                            <p class="text-sm text-yellow-800">
                                <svg class="w-4 h-4 inline mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                                        clip-rule="evenodd" />
                                </svg>
                                Votre email n'est pas vérifié.
                            <form id="send-verification" method="post" action="{{ route('verification.send') }}"
                                class="inline">
                                @csrf
                                <button type="submit"
                                    class="font-medium text-yellow-800 underline hover:text-yellow-900">
                                    Renvoyer le lien
                                </button>
                            </form>
                            </p>
                        </div>
                    @endif
                </div>

                <div>
                    <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Téléphone</label>
                    <input type="tel" id="phone" name="phone" value="{{ old('phone', $user->phone) }}"
                        placeholder="+225 XX XX XX XX XX"
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#F16A00] focus:border-[#F16A00] transition">
                    @error('phone')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center justify-between pt-4 border-t border-gray-100">
                    <button type="submit"
                        class="px-6 py-2.5 bg-[#F16A00] text-white font-medium rounded-lg hover:bg-[#CC5A00] focus:ring-2 focus:ring-[#F16A00] focus:ring-offset-2 transition">
                        Enregistrer
                    </button>
                    @if (session('status') === 'profile-updated')
                        <span class="text-sm text-green-600 flex items-center" x-data="{ show: true }" x-show="show"
                            x-init="setTimeout(() => show = false, 3000)">
                            <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                    clip-rule="evenodd" />
                            </svg>
                            Enregistré !
                        </span>
                    @endif
                </div>
            </form>
        </div>

        {{-- Sécurité --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
                <h2 class="text-lg font-semibold text-gray-900 flex items-center">
                    <svg class="w-5 h-5 mr-2 text-[#F16A00]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                    Sécurité du compte
                </h2>
            </div>
            <form method="post" action="{{ route('password.update') }}" class="p-6 space-y-5">
                @csrf
                @method('put')

                <div>
                    <label for="current_password" class="block text-sm font-medium text-gray-700 mb-1">Mot de passe
                        actuel</label>
                    <input type="password" id="current_password" name="current_password" autocomplete="current-password"
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#F16A00] focus:border-[#F16A00] transition">
                    @error('current_password', 'updatePassword')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Nouveau mot de
                        passe</label>
                    <input type="password" id="password" name="password" autocomplete="new-password"
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#F16A00] focus:border-[#F16A00] transition">
                    @error('password', 'updatePassword')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">Confirmer le
                        mot de passe</label>
                    <input type="password" id="password_confirmation" name="password_confirmation"
                        autocomplete="new-password"
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#F16A00] focus:border-[#F16A00] transition">
                    @error('password_confirmation', 'updatePassword')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center justify-between pt-4 border-t border-gray-100">
                    <button type="submit"
                        class="px-6 py-2.5 bg-gray-800 text-white font-medium rounded-lg hover:bg-gray-900 focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition">
                        Modifier le mot de passe
                    </button>
                    @if (session('status') === 'password-updated')
                        <span class="text-sm text-green-600 flex items-center" x-data="{ show: true }" x-show="show"
                            x-init="setTimeout(() => show = false, 3000)">
                            <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                    clip-rule="evenodd" />
                            </svg>
                            Mot de passe modifié !
                        </span>
                    @endif
                </div>
            </form>
        </div>
    </div>

    {{-- Actions supplémentaires --}}
    <div class="mt-6 grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
        {{-- Notifications --}}
        <a href="{{ route('notifications.preferences') }}"
            class="group bg-white rounded-xl border border-gray-100 p-5 hover:border-[#FFD0A3] hover:shadow-md transition-all">
            <div class="flex items-center gap-4">
                <div
                    class="w-12 h-12 bg-purple-100 rounded-xl flex items-center justify-center group-hover:bg-purple-200 transition">
                    <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                    </svg>
                </div>
                <div class="flex-1">
                    <h3 class="font-semibold text-gray-900 group-hover:text-[#CC5A00] transition">Notifications</h3>
                    <p class="text-sm text-gray-500">Gérer vos préférences</p>
                </div>
                <svg class="w-5 h-5 text-gray-400 group-hover:text-[#F16A00] transition" fill="none"
                    stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </div>
        </a>

        {{-- Profil public --}}
        <a href="{{ route('profile.public.edit') }}"
            class="group bg-white rounded-xl border border-gray-100 p-5 hover:border-[#FFD0A3] hover:shadow-md transition-all">
            <div class="flex items-center gap-4">
                <div
                    class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center group-hover:bg-blue-200 transition">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="flex-1">
                    <h3 class="font-semibold text-gray-900 group-hover:text-[#CC5A00] transition">Profil public</h3>
                    <p class="text-sm text-gray-500">Visibilité et bio</p>
                </div>
                <svg class="w-5 h-5 text-gray-400 group-hover:text-[#F16A00] transition" fill="none"
                    stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </div>
        </a>

        {{-- Parrainage --}}
        @if ($user->referral_code && Route::has('client.referral'))
            <a href="{{ route('client.referral') }}"
                class="group bg-white rounded-xl border border-gray-100 p-5 hover:border-[#FFD0A3] hover:shadow-md transition-all">
                <div class="flex items-center gap-4">
                    <div
                        class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center group-hover:bg-green-200 transition">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="flex-1">
                        <h3 class="font-semibold text-gray-900 group-hover:text-[#CC5A00] transition">Parrainage</h3>
                        <p class="text-sm text-gray-500">{{ number_format($user->referral_balance ?? 0, 0, ',', ' ') }}
                            FCFA de solde</p>
                    </div>
                    <svg class="w-5 h-5 text-gray-400 group-hover:text-[#F16A00] transition" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </div>
            </a>
        @elseif($user->referral_code)
            <div class="bg-white rounded-xl border border-gray-100 p-5">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="flex-1">
                        <h3 class="font-semibold text-gray-900">Code parrainage</h3>
                        <p class="text-sm text-gray-500 font-mono">{{ $user->referral_code }}</p>
                    </div>
                </div>
            </div>
        @endif

        {{-- Devenir propriétaire --}}
        @if ($user->role === 'user' && Route::has('owner.become'))
            <a href="{{ route('owner.become') }}"
                class="group bg-linear-to-br from-[#FFF4EB] to-[#FFE7D1] rounded-xl border border-[#FFD0A3] p-5 hover:shadow-md transition-all">
                <div class="flex items-center gap-4">
                    <div
                        class="w-12 h-12 bg-[#FFD0A3] rounded-xl flex items-center justify-center group-hover:bg-[#FFB46F] transition">
                        <svg class="w-6 h-6 text-[#CC5A00]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                        </svg>
                    </div>
                    <div class="flex-1">
                        <h3 class="font-semibold text-gray-900 group-hover:text-[#CC5A00] transition">Devenir propriétaire
                        </h3>
                        <p class="text-sm text-gray-600">Publiez vos logements</p>
                    </div>
                    <svg class="w-5 h-5 text-[#FF8A1F] group-hover:text-[#CC5A00] transition" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </div>
            </a>
        @endif
    </div>

    {{-- Zone danger --}}
    <div class="mt-8 bg-red-50 rounded-xl border border-red-200 overflow-hidden" x-data="{ showDelete: false }">
        <div class="px-6 py-4 border-b border-red-200 bg-red-100/50">
            <h2 class="text-lg font-semibold text-red-800 flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                Zone de danger
            </h2>
        </div>
        <div class="p-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h3 class="font-medium text-red-800">Supprimer mon compte</h3>
                    <p class="text-sm text-red-600 mt-1">Cette action est irréversible. Toutes vos données seront
                        définitivement supprimées.</p>
                </div>
                <button @click="showDelete = true"
                    class="px-4 py-2 bg-red-600 text-white font-medium rounded-lg hover:bg-red-700 focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition whitespace-nowrap">
                    Supprimer le compte
                </button>
            </div>

            {{-- Modal de confirmation --}}
            <div x-show="showDelete" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4"
                style="background: rgba(0,0,0,0.5)">
                <div @click.away="showDelete = false" class="bg-white rounded-xl max-w-md w-full p-6 shadow-xl">
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Êtes-vous sûr ?</h3>
                    <p class="text-gray-600 mb-6">Cette action supprimera définitivement votre compte et toutes vos données
                        associées.</p>

                    <form method="post" action="{{ route('profile.destroy') }}">
                        @csrf
                        @method('delete')

                        <div class="mb-4">
                            <label for="delete_password" class="block text-sm font-medium text-gray-700 mb-1">Confirmez
                                votre mot de passe</label>
                            <input type="password" id="delete_password" name="password" required
                                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500">
                            @error('password', 'userDeletion')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex gap-3">
                            <button type="button" @click="showDelete = false"
                                class="flex-1 px-4 py-2.5 border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-50 transition">
                                Annuler
                            </button>
                            <button type="submit"
                                class="flex-1 px-4 py-2.5 bg-red-600 text-white font-medium rounded-lg hover:bg-red-700 transition">
                                Supprimer définitivement
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
