<x-app-layout>
    @section('title', $metaTitle)
    @section('meta_description', $metaDescription)

    {{-- HERO TARIFS --}}
    <section class="relative bg-linear-to-br from-gray-900 via-gray-900 to-[#3d0014] text-white overflow-hidden">
        <div class="absolute inset-0 opacity-10"
            style="background-image: radial-gradient(circle at 20% 80%, #F97316 0%, transparent 50%), radial-gradient(circle at 80% 20%, #06B6D4 0%, transparent 50%);">
        </div>
        <div class="relative max-w-4xl mx-auto px-4 sm:px-6 py-16 sm:py-24 text-center">
            <div class="inline-flex items-center gap-2 bg-green-500/20 text-green-300 border border-green-500/30 px-4 py-1.5 rounded-full text-sm font-semibold mb-6">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Locataire: 0 FCFA • Propriétaire: 10% par réservation
            </div>
            <h1 class="font-sans text-4xl sm:text-5xl font-extrabold mb-4">
                Un modèle simple,<br>
                <span class="text-[#FF8A1F]">transparent et juste</span>
            </h1>
            <p class="text-gray-300 text-lg max-w-2xl mx-auto">
                Chez Rezi App, les locataires ne paient aucun frais de plateforme. Côté propriétaire, Rezi App prélève 10% sur le montant total de chaque réservation confirmée, sans abonnement mensuel ni annuel.
            </p>
        </div>
    </section>

    {{-- SECTION LOCATAIRES vs PROPRIÉTAIRES --}}
    <section class="py-16 sm:py-24 bg-white">
        <div class="max-w-6xl mx-auto px-4 sm:px-6">
            <div class="text-center mb-12">
                <h2 class="font-sans text-3xl sm:text-4xl font-extrabold text-gray-900">Pour qui, combien ?</h2>
                <p class="mt-3 text-gray-500 max-w-xl mx-auto">Deux profils, une règle simple : gratuit côté locataire, commission de 10% côté propriétaire.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">

                {{-- LOCATAIRES --}}
                <div class="relative bg-linear-to-br from-blue-50 to-cyan-50 border border-blue-100 rounded-3xl p-8">
                    <div class="w-14 h-14 bg-blue-500 rounded-2xl flex items-center justify-center mb-6 shadow-lg shadow-blue-500/25">
                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                    </div>
                    <div class="inline-block bg-blue-100 text-blue-700 text-xs font-bold px-3 py-1 rounded-full mb-4">LOCATAIRES</div>
                    <div class="flex items-end gap-2 mb-2">
                        <span class="text-5xl font-extrabold text-gray-900">0</span>
                        <span class="text-xl font-bold text-gray-500 mb-2">FCFA</span>
                    </div>
                    <p class="text-sm text-gray-500 mb-6">Pour toujours — sans frais cachés</p>
                    <ul class="space-y-3">
                        @foreach([
                            'Recherche géolocalisée avancée',
                            'Accès à toutes les résidences',
                            'Contact direct WhatsApp / appel',
                            'Aucun frais de plateforme à payer',
                            'Sans inscription obligatoire',
                            'Comparaison de résidences',
                        ] as $item)
                        <li class="flex items-center gap-3 text-sm text-gray-700">
                            <svg class="w-4 h-4 text-green-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                            </svg>
                            {{ $item }}
                        </li>
                        @endforeach
                    </ul>
                    <div class="mt-8">
                        <a href="{{ route('residences.index') }}"
                            class="block w-full text-center py-3 bg-blue-500 hover:bg-blue-600 text-white font-bold rounded-xl transition-colors shadow-lg shadow-blue-500/25">
                            Commencer la recherche →
                        </a>
                    </div>
                </div>

                {{-- PROPRIÉTAIRES --}}
                <div class="relative bg-linear-to-br from-[#FFF4EB] to-amber-50 border border-[#FFD0A3] rounded-3xl p-8">
                    <div class="absolute top-4 right-4 bg-[#F16A00] text-white text-xs font-bold px-3 py-1.5 rounded-full shadow">
                        ⭐ Recommandé
                    </div>
                    <div class="w-14 h-14 bg-[#F16A00] rounded-2xl flex items-center justify-center mb-6 shadow-lg">
                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                        </svg>
                    </div>
                    <div class="inline-block bg-[#FFE7D1] text-[#A34700] text-xs font-bold px-3 py-1 rounded-full mb-4">PROPRIÉTAIRES</div>
                    <div class="flex items-end gap-2 mb-2">
                        <span class="text-5xl font-extrabold text-gray-900">10%</span>
                        <span class="text-xl font-bold text-gray-500 mb-2">/ réservation</span>
                    </div>
                    <p class="text-sm text-gray-500 mb-6">Prélevé uniquement sur le montant total encaissé — sans abonnement</p>
                    <ul class="space-y-3">
                        @foreach([
                            'Publication en moins de 5 minutes',
                            'Aucun abonnement mensuel ou annuel',
                            '10% prélevés sur chaque réservation confirmée',
                            'Tableau de bord propriétaire complet',
                            'Chat et réservations intégrés',
                            'Contrats & documents automatisés',
                            'Support prioritaire équipe Rezi App',
                        ] as $item)
                        <li class="flex items-center gap-3 text-sm text-gray-700">
                            <svg class="w-4 h-4 text-green-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                            </svg>
                            {{ $item }}
                        </li>
                        @endforeach
                    </ul>
                    <div class="mt-8">
                        <a href="{{ route('owner.residences.create') }}"
                            class="block w-full text-center py-3 bg-[#F16A00] hover:bg-[#CC5A00] text-white font-bold rounded-xl transition-colors shadow-lg">
                            Publier gratuitement →
                        </a>
                    </div>
                </div>

            </div>
        </div>
    </section>

    {{-- OPTIONS BOOST (payantes optionnelles) --}}
    <section class="py-16 sm:py-20 bg-gray-50">
        <div class="max-w-5xl mx-auto px-4 sm:px-6">
            <div class="text-center mb-12">
                <div class="inline-flex items-center gap-2 bg-amber-100 text-amber-700 px-4 py-1.5 rounded-full text-sm font-semibold mb-4">
                    <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" />
                    </svg>
                    Options premium
                </div>
                <h2 class="font-sans text-3xl sm:text-4xl font-extrabold text-gray-900">
                    Boostez votre visibilité
                </h2>
                <p class="mt-3 text-gray-500 max-w-2xl mx-auto">
                    La commission de 10% constitue le modèle économique principal. Les options Boost restent facultatives si vous souhaitez accélérer votre visibilité.
                </p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
                @foreach($boostPlans as $plan)
                @php $popular = !empty($plan['popular']); @endphp
                <div class="bg-white rounded-2xl {{ $popular ? 'border-2 border-[#FF8A1F] shadow-xl shadow-orange-100' : 'border border-gray-200 hover:border-[#FFD0A3] hover:shadow-lg' }} p-6 text-center transition-all relative">
                    @if(!empty($plan['badge']))
                    <div class="absolute -top-3 left-1/2 -translate-x-1/2 bg-[#F16A00] text-white text-xs font-bold px-4 py-1 rounded-full">
                        {{ $plan['badge'] }}
                    </div>
                    @endif
                    <div class="text-2xl mb-3">{{ $plan['emoji'] ?? '' }}</div>
                    <h3 class="font-bold text-gray-900 text-lg mb-1">{{ $plan['name'] }}</h3>
                    <div class="text-3xl font-extrabold text-[#F16A00] mb-1">{{ number_format($plan['price'], 0, ',', ' ') }} <span class="text-base font-normal text-gray-400">FCFA</span></div>
                    <p class="text-xs text-gray-400 mb-5">{{ $plan['duration_label'] }}</p>
                    <ul class="space-y-2 text-sm text-left text-gray-600 mb-6">
                        @foreach($plan['features'] ?? [] as $feature)
                        <li class="flex gap-2"><span class="text-green-500">✓</span> {{ $feature }}</li>
                        @endforeach
                    </ul>
                    <a href="{{ route('owner.residences.create') }}"
                        class="block w-full py-2.5 rounded-xl {{ $popular ? 'bg-[#F16A00] hover:bg-[#CC5A00] text-white shadow-lg' : 'bg-gray-100 hover:bg-[#F16A00] hover:text-white text-gray-700' }} font-semibold text-sm transition-all">
                        {{ $popular ? 'Choisir ' . $plan['name'] : 'Commencer' }}
                    </a>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- COMPARAISON Rezi App vs AGENCES --}}
    <section class="py-16 sm:py-20 bg-white">
        <div class="max-w-4xl mx-auto px-4 sm:px-6">
            <div class="text-center mb-10">
                <h2 class="font-sans text-3xl font-extrabold text-gray-900">Rezi App vs les agences immobilières</h2>
                <p class="mt-2 text-gray-500">Pourquoi choisir Rezi App vous fait économiser des centaines de milliers de FCFA</p>
            </div>
            <div class="overflow-x-auto rounded-2xl border border-gray-200 shadow-sm">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-200">
                            <th class="px-6 py-4 text-left font-semibold text-gray-500">Critère</th>
                            <th class="px-6 py-4 text-center font-bold text-[#CC5A00]">Rezi App</th>
                            <th class="px-6 py-4 text-center font-semibold text-gray-400">Agences traditionnelles</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach([
                            ['Publication annonce', '✅ Gratuit', '❌ 5 000–50 000 FCFA'],
                            ['Commission propriétaire', '✅ 10% du montant total réservé', '❌ 50–100% du montant de location'],
                            ['Frais d\'agence locataire', '✅ 0 FCFA', '❌ 1–2 mois de location'],
                            ['Délai publication', '✅ < 5 minutes', '❌ 1–5 jours ouvrés'],
                            ['Contact direct propriétaire', '✅ WhatsApp/Appel direct', '❌ Intermédiaire obligatoire'],
                            ['Disponibilité', '✅ 24h/7j', '❌ Horaires bureau'],
                            ['Recherche géolocalisée', '✅ Oui, par rayon GPS', '❌ Non'],
                        ] as [$crit, $rezi, $agence])
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 font-medium text-gray-700">{{ $crit }}</td>
                            <td class="px-6 py-4 text-center font-semibold text-gray-900">{{ $rezi }}</td>
                            <td class="px-6 py-4 text-center text-gray-400">{{ $agence }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    {{-- FAQ TARIFS --}}
    <section class="py-16 sm:py-20 bg-gray-50">
        <div class="max-w-3xl mx-auto px-4 sm:px-6">
            <div class="text-center mb-10">
                <h2 class="font-sans text-3xl font-extrabold text-gray-900">Questions fréquentes</h2>
            </div>
            <div x-data="{ open: null }" class="space-y-3">
                @foreach($faqItems as $i => $faq)
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                    <button @click="open = open === {{ $i }} ? null : {{ $i }}"
                        class="w-full flex items-center justify-between px-6 py-4 text-left font-semibold text-gray-900 hover:text-[#CC5A00] transition-colors">
                        <span>{{ $faq['q'] }}</span>
                        <svg class="w-5 h-5 text-gray-400 shrink-0 transition-transform" :class="open === {{ $i }} ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                    <div x-show="open === {{ $i }}" x-collapse x-cloak class="px-6 pb-4 text-gray-500 text-sm leading-relaxed border-t border-gray-100">
                        <p class="pt-4">{{ $faq['a'] }}</p>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- CTA FINAL --}}
    <section class="py-16 sm:py-20 bg-linear-to-br from-[#F16A00] to-[#CC5A00]">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 text-center text-white">
            <h2 class="font-sans text-3xl sm:text-4xl font-extrabold mb-4">
                Prêt à commencer gratuitement ?
            </h2>
            <p class="text-[#FFE7D1] text-lg mb-8">
                Rejoignez les propriétaires qui font confiance à Rezi App pour louer plus vite et sans intermédiaire.
            </p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="{{ route('owner.residences.create') }}"
                    class="inline-flex items-center justify-center gap-2 bg-white text-[#CC5A00] hover:bg-[#FFF4EB] px-8 py-4 rounded-2xl font-bold text-base shadow-xl transition-all hover:scale-105">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Publier ma résidence
                </a>
                <a href="{{ route('residences.index') }}"
                    class="inline-flex items-center justify-center gap-2 bg-[#CC5A00]/50 hover:bg-[#CC5A00]/70 text-white border border-white/30 px-8 py-4 rounded-2xl font-bold text-base transition-all">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    Chercher un logement
                </a>
            </div>
        </div>
    </section>

</x-app-layout>
