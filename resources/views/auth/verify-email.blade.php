<x-app-layout>
    @section('title', 'Vérification email - REZI')

    <div class="min-h-[calc(100vh-64px)] flex">
        {{-- Left Side - Branding (Desktop only) --}}
        <div
            class="hidden lg:flex lg:w-1/2 bg-linear-to-br from-orange-500 via-orange-500 to-cyan-700 relative overflow-hidden">
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
                            class="text-3xl font-bold bg-linear-to-r from-orange-500 to-orange-500 bg-clip-text text-transparent">R</span>
                    </div>
                </div>

                {{-- Headline --}}
                <h1 class="text-4xl font-bold text-center mb-4">Presque terminé !</h1>
                <p class="text-xl text-orange-100 text-center mb-12 max-w-md">
                    Il ne reste qu'une étape pour activer votre compte REZI.
                </p>

                {{-- Illustration - Email --}}
                <div class="w-32 h-32 bg-white/10 backdrop-blur-sm rounded-3xl flex items-center justify-center mb-8">
                    <svg class="w-16 h-16 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                </div>

                {{-- Features --}}
                <div class="space-y-4 w-full max-w-sm">
                    <div class="flex items-center gap-4 bg-white/10 backdrop-blur-sm rounded-xl p-4">
                        <div class="w-10 h-10 bg-white/20 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <div class="font-semibold">Sécurité renforcée</div>
                            <div class="text-sm text-orange-100">Protégez votre compte</div>
                        </div>
                    </div>
                    <div class="flex items-center gap-4 bg-white/10 backdrop-blur-sm rounded-xl p-4">
                        <div class="w-10 h-10 bg-white/20 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                            </svg>
                        </div>
                        <div>
                            <div class="font-semibold">Notifications</div>
                            <div class="text-sm text-orange-100">Recevez les alertes</div>
                        </div>
                    </div>
                    <div class="flex items-center gap-4 bg-white/10 backdrop-blur-sm rounded-xl p-4">
                        <div class="w-10 h-10 bg-white/20 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                            </svg>
                        </div>
                        <div>
                            <div class="font-semibold">Favoris & réservations</div>
                            <div class="text-sm text-orange-100">Sauvegardez vos coups de cœur</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Right Side - Verification --}}
        <div class="w-full lg:w-1/2 flex flex-col justify-center items-center p-6 sm:p-12 bg-gray-50">
            <div class="w-full max-w-md">
                {{-- Header --}}
                <div class="text-center mb-8">
                    {{-- Mobile logo --}}
                    <div class="lg:hidden mb-6 flex justify-center">
                        <div
                            class="w-16 h-16 bg-linear-to-br from-orange-500 to-orange-600 rounded-2xl flex items-center justify-center shadow-xl">
                            <span class="text-2xl font-bold text-white">R</span>
                        </div>
                    </div>

                    {{-- Email Icon --}}
                    <div class="mx-auto w-20 h-20 bg-orange-100 rounded-2xl flex items-center justify-center mb-6">
                        <svg class="w-10 h-10 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                    </div>

                    <h2 class="text-3xl font-bold text-gray-900">Vérifiez votre email</h2>
                    <p class="mt-3 text-gray-600 leading-relaxed">
                        Merci de vous être inscrit ! Avant de commencer, veuillez vérifier votre adresse email en
                        cliquant sur le lien que nous venons de vous envoyer.
                    </p>
                </div>

                {{-- Success Status --}}
                @if (session('status') == 'verification-link-sent')
                    <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-xl flex items-start gap-3">
                        <svg class="w-5 h-5 text-green-500 mt-0.5 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                clip-rule="evenodd" />
                        </svg>
                        <p class="text-sm text-green-700 font-medium">
                            Un nouveau lien de vérification a été envoyé à l'adresse email que vous avez fournie lors de
                            l'inscription.
                        </p>
                    </div>
                @endif

                {{-- Actions --}}
                <div class="space-y-4">
                    {{-- Resend --}}
                    <form method="POST" action="{{ route('verification.send') }}">
                        @csrf
                        <button type="submit"
                            class="w-full bg-linear-to-r from-orange-500 to-orange-500 hover:from-orange-600 hover:to-teal-700 text-white font-semibold py-3.5 px-4 rounded-xl transition-all duration-200 shadow-lg shadow-orange-500/30 hover:shadow-xl hover:shadow-orange-500/40 flex items-center justify-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                            <span>Renvoyer l'email de vérification</span>
                        </button>
                    </form>

                    {{-- Logout --}}
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit"
                            class="w-full bg-white border border-gray-200 text-gray-700 font-medium py-3.5 px-4 rounded-xl hover:bg-gray-50 hover:border-gray-300 transition-all flex items-center justify-center gap-2">
                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                            </svg>
                            <span>Se déconnecter</span>
                        </button>
                    </form>
                </div>

                {{-- Help text --}}
                <div class="mt-8 p-4 bg-blue-50 border border-blue-100 rounded-xl">
                    <div class="flex items-start gap-3">
                        <svg class="w-5 h-5 text-blue-500 mt-0.5 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                                clip-rule="evenodd" />
                        </svg>
                        <div class="text-sm text-blue-700">
                            <p class="font-medium mb-1">Vous ne trouvez pas l'email ?</p>
                            <p>Vérifiez votre dossier spam ou courrier indésirable. L'email provient de
                                <strong>{{ config('mail.from.address') }}</strong>.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
