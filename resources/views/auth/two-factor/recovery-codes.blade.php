@extends('layouts.owner')

@section('title', 'Codes de récupération - ReziApp')

@section('owner-content')
    <div class="max-w-lg mx-auto space-y-6" x-data="{ confirmed: false }">

        {{-- ============================== HEADER ============================== --}}
        <div class="text-center pt-4">
            {{-- Animation de succès --}}
            <div class="relative w-20 h-20 mx-auto mb-5">
                <div class="absolute inset-0 rounded-full bg-emerald-100 animate-ping-slow opacity-40"></div>
                <div class="relative w-20 h-20 rounded-full bg-emerald-50 flex items-center justify-center">
                    <svg class="w-10 h-10 text-emerald-600 animate-check" fill="none" stroke="currentColor"
                        stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
                    </svg>
                </div>
            </div>

            <h1 class="text-2xl font-bold text-gray-900">2FA activée avec succès !</h1>
            <p class="text-sm text-gray-500 mt-2 max-w-sm mx-auto">
                Sauvegardez ces codes de récupération dans un endroit sûr.
                <span class="font-semibold text-gray-900">Ils ne seront plus affichés.</span>
            </p>
        </div>

        {{-- ============================== AVERTISSEMENT ============================== --}}
        <div class="flex items-start gap-3 px-4 py-3 bg-amber-50 border border-amber-200 rounded-xl">
            <svg class="w-5 h-5 text-amber-600 shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd"
                    d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 6a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 6zm0 9a1 1 0 100-2 1 1 0 000 2z"
                    clip-rule="evenodd" />
            </svg>
            <div>
                <p class="text-sm font-semibold text-amber-800">Important</p>
                <p class="text-xs text-amber-700 mt-0.5">
                    Chaque code ne peut être utilisé qu'<strong>une seule fois</strong> pour vous connecter si vous perdez accès à votre application d'authentification.
                </p>
            </div>
        </div>

        {{-- ============================== GRILLE DES CODES ============================== --}}
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
            <div class="p-5 sm:p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-sm font-semibold text-gray-900">Vos 8 codes de récupération</h2>
                    <span class="text-[11px] font-medium text-gray-400 bg-gray-100 px-2 py-0.5 rounded-full">Usage unique</span>
                </div>

                <div class="grid grid-cols-2 gap-2" id="recovery-codes-grid">
                    @foreach ($codes as $index => $code)
                        <div class="flex items-center gap-2 px-3 py-2.5 bg-gray-50 rounded-xl font-mono text-sm font-bold text-gray-900 tracking-wider select-all">
                            <span class="text-[10px] font-medium text-gray-400 w-4">{{ $index + 1 }}.</span>
                            {{ $code }}
                        </div>
                    @endforeach
                </div>

                {{-- Actions --}}
                <div class="flex gap-2 mt-4" x-data="{ copiedAll: false }">
                    <button type="button"
                        @click="
                            const codes = @js(implode('\n', $codes));
                            navigator.clipboard.writeText(codes);
                            copiedAll = true;
                            setTimeout(() => copiedAll = false, 3000);
                        "
                        class="flex-1 inline-flex items-center justify-center gap-2 px-3 py-2.5 text-sm font-medium rounded-xl border border-gray-200 text-gray-700 hover:bg-gray-50 transition-all">
                        <template x-if="!copiedAll">
                            <span class="inline-flex items-center gap-1.5">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M15.666 3.888A2.25 2.25 0 0013.5 2.25h-3c-1.03 0-1.9.693-2.166 1.638m7.332 0c.055.194.084.4.084.612v0a.75.75 0 01-.75.75H9.75a.75.75 0 01-.75-.75v0c0-.212.03-.418.084-.612m7.332 0c.646.049 1.288.11 1.927.184 1.1.128 1.907 1.077 1.907 2.185V19.5a2.25 2.25 0 01-2.25 2.25H6.75A2.25 2.25 0 014.5 19.5V6.257c0-1.108.806-2.057 1.907-2.185a48.208 48.208 0 011.927-.184" />
                                </svg>
                                Copier
                            </span>
                        </template>
                        <template x-if="copiedAll">
                            <span class="inline-flex items-center gap-1.5 text-emerald-600">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                </svg>
                                Copié !
                            </span>
                        </template>
                    </button>

                    <button type="button" onclick="printCodes()"
                        class="flex-1 inline-flex items-center justify-center gap-2 px-3 py-2.5 text-sm font-medium rounded-xl border border-gray-200 text-gray-700 hover:bg-gray-50 transition-all">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0l.229 2.523a1.125 1.125 0 01-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0021 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 00-1.913-.247M6.34 18H5.25A2.25 2.25 0 013 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 011.913-.247m10.5 0a48.536 48.536 0 00-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5zm-3 0h.008v.008H15V10.5z" />
                        </svg>
                        Imprimer
                    </button>

                    <a href="data:text/plain;charset=utf-8,{{ urlencode("ReziApp - Codes de récupération 2FA\n" . str_repeat('=', 35) . "\n\n" . implode("\n", array_map(fn($c, $i) => ($i+1) . '. ' . $c, $codes, array_keys($codes))) . "\n\nChaque code ne peut être utilisé qu'une seule fois.\nGardez-les dans un endroit sûr.") }}"
                        download="rezi-2fa-recovery-codes.txt"
                        class="flex-1 inline-flex items-center justify-center gap-2 px-3 py-2.5 text-sm font-medium rounded-xl border border-gray-200 text-gray-700 hover:bg-gray-50 transition-all">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                        </svg>
                        .txt
                    </a>
                </div>
            </div>
        </div>

        {{-- ============================== CONFIRMATION ============================== --}}
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
            <div class="p-5 sm:p-6">
                <label class="flex items-start gap-3 cursor-pointer group" @click="confirmed = !confirmed">
                    <input type="checkbox" x-model="confirmed"
                        class="mt-0.5 w-4 h-4 rounded border-gray-300 text-gray-900 focus:ring-gray-900 focus:ring-offset-0">
                    <span class="text-sm text-gray-600 group-hover:text-gray-900 transition-colors">
                        J'ai bien sauvegardé mes codes de récupération dans un endroit sûr. Je comprends que si je perds ces codes <strong>et</strong> mon application d'authentification, je devrai contacter le support.
                    </span>
                </label>

                <form action="{{ route('two-factor.recovery-codes.confirm') }}" method="POST" class="mt-4">
                    @csrf
                    <button type="submit" :disabled="!confirmed"
                        class="w-full px-4 py-3.5 bg-gray-900 text-white text-sm font-bold rounded-xl hover:bg-gray-800 transition-all disabled:opacity-30 disabled:cursor-not-allowed">
                        <span class="inline-flex items-center gap-2 justify-center">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                            </svg>
                            J'ai sauvegardé mes codes, continuer
                        </span>
                    </button>
                </form>
            </div>
        </div>
    </div>

    <style>
        @keyframes ping-slow {
            0% { transform: scale(1); opacity: 0.4; }
            75%, 100% { transform: scale(1.5); opacity: 0; }
        }
        .animate-ping-slow {
            animation: ping-slow 2s cubic-bezier(0, 0, 0.2, 1) infinite;
        }
        @keyframes check-draw {
            from { stroke-dashoffset: 100; }
            to { stroke-dashoffset: 0; }
        }
        .animate-check path {
            stroke-dasharray: 100;
            animation: check-draw 0.6s ease-out 0.3s forwards;
        }
        @media print {
            body * { visibility: hidden; }
            #recovery-codes-grid, #recovery-codes-grid * { visibility: visible; }
            #recovery-codes-grid { position: absolute; left: 0; top: 0; }
        }
    </style>

    <script>
        function printCodes() {
            const codes = @json($codes);
            const printWindow = window.open('', '_blank', 'width=400,height=500');
            printWindow.document.write(`
                <html><head><title>ReziApp - Codes de récupération 2FA</title>
                <style>
                    body { font-family: -apple-system, system-ui, sans-serif; padding: 40px; color: #111; }
                    h1 { font-size: 18px; margin-bottom: 4px; }
                    p { font-size: 12px; color: #666; margin-bottom: 20px; }
                    .code { font-family: monospace; font-size: 16px; font-weight: bold; padding: 8px 12px; margin: 4px 0; background: #f5f5f5; border-radius: 8px; letter-spacing: 2px; }
                    .warning { font-size: 11px; color: #92400e; margin-top: 20px; padding: 10px; background: #fffbeb; border-radius: 8px; }
                </style></head><body>
                <h1>🔐 ReziApp - Codes de récupération</h1>
                <p>Double authentification · ${new Date().toLocaleDateString('fr-FR')}</p>
                ${codes.map((c, i) => '<div class="code">' + (i+1) + '. ' + c + '</div>').join('')}
                <div class="warning">⚠️ Chaque code ne peut être utilisé qu'une seule fois. Gardez cette page dans un endroit sûr.</div>
                </body></html>
            `);
            printWindow.document.close();
            printWindow.print();
        }
    </script>
@endsection
