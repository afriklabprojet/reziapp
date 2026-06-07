@extends('layouts.owner')

@section('title', 'Partager mon code de parrainage')

@section('owner-content')
    <div class="max-w-2xl mx-auto space-y-6" x-data="shareReferral">
        {{-- Header --}}
        <div>
            <a href="{{ route('owner.marketing.referrals.index') }}"
                class="inline-flex items-center gap-2 text-gray-500 hover:text-gray-900 text-sm mb-4 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
                Retour au parrainage
            </a>
            <h1 class="text-2xl font-bold text-gray-900">Partager mon code</h1>
            <p class="text-gray-500 mt-1">Invitez vos amis à rejoindre Rezi Studio Meublé Faya et gagnez des récompenses</p>
        </div>

        {{-- Referral Code Card --}}
        <div
            class="bg-linear-to-br from-[#F16A00] via-[#CC5A00] to-[#A34700] rounded-2xl p-8 text-white text-center relative overflow-hidden shadow-lg">
            <div class="absolute top-0 right-0 w-40 h-40 bg-white/5 rounded-full -translate-y-1/2 translate-x-1/2"></div>
            <div class="absolute bottom-0 left-0 w-24 h-24 bg-white/5 rounded-full translate-y-1/2 -translate-x-1/2"></div>

            <div class="relative">
                <div
                    class="w-16 h-16 bg-white/15 backdrop-blur-sm rounded-2xl flex items-center justify-center mx-auto mb-4 border border-white/20">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197" />
                    </svg>
                </div>
                <p class="text-[#FFE7D1] text-sm mb-2">Votre code de parrainage</p>
                <code
                    class="block text-3xl font-mono font-bold tracking-[0.15em] mb-4">{{ auth()->user()->referral_code }}</code>
                <p class="text-[#FFE7D1] text-sm mb-6">
                    Gagnez <strong
                        class="text-white">{{ number_format(config('rezi.referral.referrer_reward', 5000), 0, ',', ' ') }}
                        FCFA</strong> par filleul qualifié
                </p>

                {{-- Copy URL --}}
                <div class="bg-white/10 rounded-xl p-2.5 flex items-center gap-2 max-w-md mx-auto border border-white/10">
                    <input type="text" readonly value="{{ route('register', ['ref' => auth()->user()->referral_code]) }}"
                        id="referral-url"
                        class="flex-1 bg-transparent border-0 text-white/90 text-sm text-center focus:outline-none focus:ring-0 truncate px-2" />
                    <button @click="copyUrl()"
                        class="px-4 py-2 bg-white/20 rounded-lg hover:bg-white/30 transition text-sm font-medium whitespace-nowrap">
                        <span x-show="!copied">
                            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                            </svg>
                            Copier
                        </span>
                        <span x-show="copied" x-cloak class="text-green-300">✓ Copié !</span>
                    </button>
                </div>
            </div>
        </div>

        {{-- Share Channels --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100">
                <h3 class="font-semibold text-gray-900">Partager via</h3>
            </div>
            <div class="p-6 grid grid-cols-2 gap-3">
                <a href="{{ route('owner.marketing.referrals.share', ['channel' => 'whatsapp']) }}"
                    class="flex items-center gap-3 p-4 rounded-xl border border-gray-200 hover:border-green-300 hover:bg-green-50 transition-all group">
                    <div
                        class="w-12 h-12 bg-[#25D366] rounded-xl flex items-center justify-center text-white shrink-0 shadow-sm">
                        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                            <path
                                d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z" />
                        </svg>
                    </div>
                    <div>
                        <p class="font-semibold text-gray-900 text-sm group-hover:text-green-700">WhatsApp</p>
                        <p class="text-xs text-gray-500">Envoyer à vos contacts</p>
                    </div>
                </a>

                <a href="{{ route('owner.marketing.referrals.share', ['channel' => 'facebook']) }}"
                    class="flex items-center gap-3 p-4 rounded-xl border border-gray-200 hover:border-blue-300 hover:bg-blue-50 transition-all group">
                    <div
                        class="w-12 h-12 bg-[#1877F2] rounded-xl flex items-center justify-center text-white shrink-0 shadow-sm">
                        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                            <path
                                d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z" />
                        </svg>
                    </div>
                    <div>
                        <p class="font-semibold text-gray-900 text-sm group-hover:text-blue-700">Facebook</p>
                        <p class="text-xs text-gray-500">Partager sur votre profil</p>
                    </div>
                </a>

                <a href="{{ route('owner.marketing.referrals.share', ['channel' => 'twitter']) }}"
                    class="flex items-center gap-3 p-4 rounded-xl border border-gray-200 hover:border-gray-400 hover:bg-gray-50 transition-all group">
                    <div
                        class="w-12 h-12 bg-gray-900 rounded-xl flex items-center justify-center text-white shrink-0 shadow-sm">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                            <path
                                d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z" />
                        </svg>
                    </div>
                    <div>
                        <p class="font-semibold text-gray-900 text-sm group-hover:text-gray-700">X (Twitter)</p>
                        <p class="text-xs text-gray-500">Tweeter votre code</p>
                    </div>
                </a>

                <a href="{{ route('owner.marketing.referrals.share', ['channel' => 'email']) }}"
                    class="flex items-center gap-3 p-4 rounded-xl border border-gray-200 hover:border-[#FFD0A3] hover:bg-[#FFF4EB] transition-all group">
                    <div
                        class="w-12 h-12 bg-[#F16A00] rounded-xl flex items-center justify-center text-white shrink-0 shadow-sm">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <div>
                        <p class="font-semibold text-gray-900 text-sm group-hover:text-[#A34700]">Email</p>
                        <p class="text-xs text-gray-500">Inviter par email</p>
                    </div>
                </a>
            </div>
        </div>

        {{-- How Rewards Work --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
            <h3 class="font-semibold text-gray-900 mb-5">Comment ça marche ?</h3>
            <div class="space-y-4">
                <div class="flex items-start gap-4">
                    <div
                        class="w-8 h-8 bg-[#FFE7D1] text-[#CC5A00] rounded-lg flex items-center justify-center font-bold text-sm shrink-0">
                        1</div>
                    <div>
                        <p class="font-medium text-gray-900 text-sm">Partagez votre code</p>
                        <p class="text-sm text-gray-500">Envoyez votre lien de parrainage à vos amis via WhatsApp, Facebook
                            ou email.</p>
                    </div>
                </div>
                <div class="flex items-start gap-4">
                    <div
                        class="w-8 h-8 bg-blue-100 text-blue-600 rounded-lg flex items-center justify-center font-bold text-sm shrink-0">
                        2</div>
                    <div>
                        <p class="font-medium text-gray-900 text-sm">Votre ami s'inscrit</p>
                        <p class="text-sm text-gray-500">Il utilise votre code lors de son inscription et effectue sa
                            première réservation.</p>
                    </div>
                </div>
                <div class="flex items-start gap-4">
                    <div
                        class="w-8 h-8 bg-green-100 text-green-600 rounded-lg flex items-center justify-center font-bold text-sm shrink-0">
                        3</div>
                    <div>
                        <p class="font-medium text-gray-900 text-sm">Recevez votre récompense</p>
                        <p class="text-sm text-gray-500">
                            Vous gagnez
                            <strong>{{ number_format(config('rezi.referral.referrer_reward', 5000), 0, ',', ' ') }}
                                FCFA</strong> et
                            votre filleul reçoit
                            <strong>{{ number_format(config('rezi.referral.referred_reward', 2500), 0, ',', ' ') }}
                                FCFA</strong> de remise.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('alpine:init', () => {
                Alpine.data('shareReferral', () => ({
                    copied: false,
                    copyUrl() {
                        const url = document.getElementById('referral-url').value;
                        navigator.clipboard.writeText(url).then(() => {
                            this.copied = true;
                            setTimeout(() => this.copied = false, 2000);
                        });
                    }
                }));
            });
        </script>
    @endpush
@endsection
