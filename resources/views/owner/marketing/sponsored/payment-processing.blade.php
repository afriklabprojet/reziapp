@extends('layouts.owner')

@section('title', 'Traitement du paiement')

@section('owner-content')
    <div class="max-w-lg mx-auto py-12" x-data="paymentPolling()" x-init="startPolling()">

        {{-- Processing State --}}
        <div x-show="status === 'pending'" class="text-center space-y-6">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8 space-y-6">
                {{-- Animated spinner --}}
                <div class="flex justify-center">
                    <div class="relative">
                        <div class="w-20 h-20 rounded-full border-4 border-gray-100"></div>
                        <div class="absolute inset-0 w-20 h-20 rounded-full border-4 border-transparent border-t-orange-500 animate-spin"></div>
                        <div class="absolute inset-0 flex items-center justify-center">
                            <svg class="w-8 h-8 text-[#ff385c]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z" />
                            </svg>
                        </div>
                    </div>
                </div>

                <div>
                    <h2 class="text-xl font-bold text-gray-900">Traitement en cours...</h2>
                    <p class="text-sm text-gray-500 mt-2 leading-relaxed">
                        Votre paiement est en cours de vérification.<br>
                        Cette page se mettra à jour automatiquement.
                    </p>
                </div>

                {{-- Progress dots --}}
                <div class="flex items-center justify-center gap-2">
                    <div class="w-2 h-2 rounded-full bg-[#ff385c] animate-bounce" style="animation-delay: 0s"></div>
                    <div class="w-2 h-2 rounded-full bg-[#ff385c] animate-bounce" style="animation-delay: 0.2s"></div>
                    <div class="w-2 h-2 rounded-full bg-[#ff385c] animate-bounce" style="animation-delay: 0.4s"></div>
                </div>

                {{-- Details --}}
                <div class="bg-gray-50 rounded-xl p-4 space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-500">Campagne</span>
                        <span class="font-semibold text-gray-900">{{ $sponsored->type_label }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Montant</span>
                        <span class="font-semibold text-gray-900">{{ number_format($sponsored->total_budget ?? 0, 0, ',', ' ') }} FCFA</span>
                    </div>
                    @if ($sponsored->payment_method)
                        <div class="flex justify-between">
                            <span class="text-gray-500">Méthode</span>
                            <span class="font-semibold text-gray-900 capitalize">{{ $sponsored->payment_method }}</span>
                        </div>
                    @endif
                </div>

                <p class="text-xs text-gray-400">
                    Vérification automatique toutes les 5 secondes • Tentative <span x-text="attempts"></span>
                </p>
            </div>

            {{-- Manual retry --}}
            <a href="{{ route('owner.marketing.sponsored.payment', $sponsored) }}"
                class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-900 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
                </svg>
                Choisir un autre moyen de paiement
            </a>
        </div>

        {{-- Success State --}}
        <div x-show="status === 'success'" x-cloak class="text-center space-y-6">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8 space-y-6">
                <div class="flex justify-center">
                    <div class="w-20 h-20 rounded-full bg-green-50 flex items-center justify-center">
                        <svg class="w-10 h-10 text-green-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>

                <div>
                    <h2 class="text-xl font-bold text-gray-900">Paiement confirmé !</h2>
                    <p class="text-sm text-gray-500 mt-2">
                        Votre mise en avant est maintenant active. Redirection en cours...
                    </p>
                </div>
            </div>
        </div>

        {{-- Error State --}}
        <div x-show="status === 'error'" x-cloak class="text-center space-y-6">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8 space-y-6">
                <div class="flex justify-center">
                    <div class="w-20 h-20 rounded-full bg-red-50 flex items-center justify-center">
                        <svg class="w-10 h-10 text-red-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                        </svg>
                    </div>
                </div>

                <div>
                    <h2 class="text-xl font-bold text-gray-900">Paiement échoué</h2>
                    <p class="text-sm text-gray-500 mt-2">
                        Le paiement n'a pas pu être confirmé. Veuillez réessayer.
                    </p>
                </div>

                <a href="{{ route('owner.marketing.sponsored.payment', $sponsored) }}"
                    class="inline-flex items-center justify-center gap-2 bg-gray-900 text-white px-6 py-3 rounded-xl font-semibold text-sm shadow-sm hover:bg-gray-800 transition-all">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182" />
                    </svg>
                    Réessayer le paiement
                </a>
            </div>
        </div>
    </div>

    <script>
        function paymentPolling() {
            return {
                status: 'pending',
                attempts: 0,
                maxAttempts: 60,
                interval: null,

                startPolling() {
                    this.interval = setInterval(() => {
                        this.checkPayment();
                    }, 5000);
                },

                async checkPayment() {
                    this.attempts++;

                    if (this.attempts > this.maxAttempts) {
                        clearInterval(this.interval);
                        this.status = 'error';
                        return;
                    }

                    try {
                        const response = await fetch('{{ route("payment.jeko.check", $sponsored) }}', {
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            credentials: 'same-origin',
                        });

                        if (!response.ok) return;

                        const data = await response.json();

                        if (data.status === 'success') {
                            clearInterval(this.interval);
                            this.status = 'success';
                            setTimeout(() => {
                                window.location.href = data.redirect;
                            }, 2000);
                        } else if (data.status === 'error') {
                            clearInterval(this.interval);
                            this.status = 'error';
                        }
                    } catch (e) {
                        // Silently continue polling
                    }
                }
            };
        }
    </script>
@endsection
