<x-app-layout>
    @section('title', 'Mot de passe oublié - REZI')

    <div class="min-h-[calc(100vh-64px)] flex">
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
                <h1 class="text-4xl font-bold text-center mb-4">Pas de panique !</h1>
                <p class="text-xl text-[#FFE7D1] text-center mb-12 max-w-md">
                    Nous allons vous aider à récupérer l'accès à votre compte en quelques secondes.
                </p>

                {{-- Illustration --}}
                <div class="w-32 h-32 bg-white/10 backdrop-blur-sm rounded-3xl flex items-center justify-center mb-8">
                    <svg class="w-16 h-16 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                    </svg>
                </div>

                {{-- Steps --}}
                <div class="space-y-4 w-full max-w-sm">
                    <div class="flex items-center gap-4 bg-white/10 backdrop-blur-sm rounded-xl p-4">
                        <div
                            class="w-10 h-10 bg-white/20 rounded-lg flex items-center justify-center font-bold text-lg">
                            1</div>
                        <div>
                            <div class="font-semibold">Entrez votre email</div>
                            <div class="text-sm text-[#FFE7D1]">Celui utilisé à l'inscription</div>
                        </div>
                    </div>
                    <div class="flex items-center gap-4 bg-white/10 backdrop-blur-sm rounded-xl p-4">
                        <div
                            class="w-10 h-10 bg-white/20 rounded-lg flex items-center justify-center font-bold text-lg">
                            2</div>
                        <div>
                            <div class="font-semibold">Vérifiez votre boîte mail</div>
                            <div class="text-sm text-[#FFE7D1]">Un lien vous sera envoyé</div>
                        </div>
                    </div>
                    <div class="flex items-center gap-4 bg-white/10 backdrop-blur-sm rounded-xl p-4">
                        <div
                            class="w-10 h-10 bg-white/20 rounded-lg flex items-center justify-center font-bold text-lg">
                            3</div>
                        <div>
                            <div class="font-semibold">Créez un nouveau mot de passe</div>
                            <div class="text-sm text-[#FFE7D1]">Et reconnectez-vous !</div>
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
                    <h2 class="text-3xl font-bold text-gray-900">Mot de passe oublié ?</h2>
                    <p class="mt-2 text-gray-600">Indiquez votre adresse email et nous vous enverrons un lien de
                        réinitialisation.</p>
                </div>

                {{-- Session Status --}}
                <x-auth-session-status class="mb-4" :status="session('status')" />

                {{-- Form --}}
                <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
                    @csrf

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
                            <input id="email" name="email" type="email" value="{{ old('email') }}" required
                                autofocus autocomplete="email" placeholder="vous@exemple.com"
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

                    {{-- Submit --}}
                    <button type="submit"
                        class="w-full bg-linear-to-r from-[#F16A00] to-[#F16A00] hover:from-[#CC5A00] hover:to-teal-700 text-white font-semibold py-3.5 px-4 rounded-xl transition-all duration-200 shadow-lg shadow-none hover:shadow-xl hover:shadow-none/40 flex items-center justify-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                        <span>Envoyer le lien de réinitialisation</span>
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
