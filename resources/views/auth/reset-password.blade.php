<x-app-layout>
    @section('title', 'Nouveau mot de passe - Rezi App')

    <div class="min-h-[calc(100vh-64px)] flex" x-data="{ showPassword: false, showConfirm: false }">
        {{-- Left Side - Branding (Desktop only) --}}
        <div
            class="hidden lg:flex lg:w-1/2 bg-linear-to-br from-[#F16A00] via-[#F16A00] to-cyan-700 relative overflow-hidden">
            {{-- Background Pattern --}}
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

            {{-- Floating Elements --}}
            <div class="absolute top-20 left-10 w-20 h-20 bg-white/10 rounded-2xl backdrop-blur-sm animate-pulse"></div>
            <div class="absolute top-40 right-20 w-16 h-16 bg-white/10 rounded-full backdrop-blur-sm animate-bounce"
                style="animation-duration: 3s;"></div>
            <div class="absolute bottom-32 left-20 w-24 h-24 bg-white/10 rounded-3xl backdrop-blur-sm animate-pulse"
                style="animation-delay: 1s;"></div>
            <div class="absolute bottom-20 right-10 w-14 h-14 bg-white/10 rounded-xl backdrop-blur-sm animate-bounce"
                style="animation-duration: 4s;"></div>

            {{-- Content --}}
            <div class="relative z-10 flex flex-col justify-center items-center w-full p-12 text-white">
                {{-- Logo --}}
                <div class="mb-8">
                    <div class="w-20 h-20 bg-white rounded-2xl flex items-center justify-center shadow-2xl">
                        <span
                            class="text-3xl font-bold bg-linear-to-r from-[#F16A00] to-[#F16A00] bg-clip-text text-transparent">R</span>
                    </div>
                </div>

                {{-- Headline --}}
                <h1 class="text-4xl font-bold text-center mb-4">Nouveau départ</h1>
                <p class="text-xl text-[#FFE7D1] text-center mb-12 max-w-md">
                    Choisissez un mot de passe sécurisé pour protéger votre compte Rezi App.
                </p>

                {{-- Illustration --}}
                <div class="w-32 h-32 bg-white/10 backdrop-blur-sm rounded-3xl flex items-center justify-center mb-8">
                    <svg class="w-16 h-16 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                </div>

                {{-- Tips --}}
                <div class="space-y-4 w-full max-w-sm">
                    <div class="flex items-center gap-4 bg-white/10 backdrop-blur-sm rounded-xl p-4">
                        <div class="w-10 h-10 bg-white/20 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <div class="font-semibold">Minimum 8 caractères</div>
                            <div class="text-sm text-[#FFE7D1]">Plus c'est long, mieux c'est</div>
                        </div>
                    </div>
                    <div class="flex items-center gap-4 bg-white/10 backdrop-blur-sm rounded-xl p-4">
                        <div class="w-10 h-10 bg-white/20 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <div class="font-semibold">Mélangez les caractères</div>
                            <div class="text-sm text-[#FFE7D1]">Lettres, chiffres, symboles</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Right Side - Form --}}
        <div class="w-full lg:w-1/2 flex flex-col justify-center items-center p-6 sm:p-12 bg-gray-50">
            <div class="w-full max-w-md">
                {{-- Header --}}
                <div class="text-center mb-8">
                    {{-- Mobile logo --}}
                    <div class="lg:hidden mb-6 flex justify-center">
                        <div
                            class="w-16 h-16 bg-linear-to-br from-[#F16A00] to-[#CC5A00] rounded-2xl flex items-center justify-center shadow-xl">
                            <span class="text-2xl font-bold text-white">R</span>
                        </div>
                    </div>
                    <h2 class="text-3xl font-bold text-gray-900">Nouveau mot de passe</h2>
                    <p class="mt-2 text-gray-600">Choisissez un nouveau mot de passe pour votre compte.</p>
                </div>

                {{-- Form --}}
                <form method="POST" action="{{ route('password.store') }}" class="space-y-5">
                    @csrf

                    {{-- Password Reset Token --}}
                    <input type="hidden" name="token" value="{{ $request->route('token') }}">

                    {{-- Email --}}
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Adresse email</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207" />
                                </svg>
                            </div>
                            <input id="email" name="email" type="email"
                                value="{{ old('email', $request->email) }}" required autofocus autocomplete="username"
                                placeholder="vous@exemple.com"
                                class="w-full pl-12 pr-4 py-3.5 bg-white border border-gray-200 rounded-xl text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#F16A00] focus:border-transparent transition-all">
                        </div>
                        @error('email')
                            <p class="mt-2 text-sm text-red-600 flex items-center gap-1">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                                        clip-rule="evenodd" />
                                </svg>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    {{-- Password --}}
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-2">Nouveau mot de
                            passe</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                </svg>
                            </div>
                            <input id="password" name="password" :type="showPassword ? 'text' : 'password'" required
                                autocomplete="new-password" placeholder="••••••••"
                                class="w-full pl-12 pr-12 py-3.5 bg-white border border-gray-200 rounded-xl text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#F16A00] focus:border-transparent transition-all">
                            <button type="button" @click="showPassword = !showPassword"
                                class="absolute inset-y-0 right-0 pr-4 flex items-center text-gray-400 hover:text-gray-600">
                                <svg x-show="!showPassword" class="w-5 h-5" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                                <svg x-show="showPassword" class="w-5 h-5" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24" style="display: none;">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                                </svg>
                            </button>
                        </div>
                        @error('password')
                            <p class="mt-2 text-sm text-red-600 flex items-center gap-1">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                                        clip-rule="evenodd" />
                                </svg>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    {{-- Confirm Password --}}
                    <div>
                        <label for="password_confirmation"
                            class="block text-sm font-medium text-gray-700 mb-2">Confirmer le mot de passe</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                </svg>
                            </div>
                            <input id="password_confirmation" name="password_confirmation"
                                :type="showConfirm ? 'text' : 'password'" required autocomplete="new-password"
                                placeholder="••••••••"
                                class="w-full pl-12 pr-12 py-3.5 bg-white border border-gray-200 rounded-xl text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#F16A00] focus:border-transparent transition-all">
                            <button type="button" @click="showConfirm = !showConfirm"
                                class="absolute inset-y-0 right-0 pr-4 flex items-center text-gray-400 hover:text-gray-600">
                                <svg x-show="!showConfirm" class="w-5 h-5" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                                <svg x-show="showConfirm" class="w-5 h-5" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24" style="display: none;">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                                </svg>
                            </button>
                        </div>
                        @error('password_confirmation')
                            <p class="mt-2 text-sm text-red-600 flex items-center gap-1">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                                        clip-rule="evenodd" />
                                </svg>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    {{-- Submit --}}
                    <button type="submit"
                        class="w-full bg-linear-to-r from-[#F16A00] to-[#F16A00] hover:from-[#CC5A00] hover:to-teal-700 text-white font-semibold py-3.5 px-4 rounded-xl transition-all duration-200 shadow-lg shadow-none hover:shadow-xl hover:shadow-none/40 flex items-center justify-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                        <span>Réinitialiser le mot de passe</span>
                    </button>
                </form>

                {{-- Back to login --}}
                <p class="mt-8 text-center text-gray-600">
                    Vous vous souvenez ?
                    <a href="{{ route('login') }}"
                        class="font-semibold text-[#F16A00] hover:text-[#CC5A00] transition-colors">
                        Se connecter
                    </a>
                </p>
            </div>
        </div>
    </div>
</x-app-layout>
