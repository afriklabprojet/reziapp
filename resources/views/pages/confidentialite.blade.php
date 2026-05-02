<x-app-layout>
    @section('title', $metaTitle ?? $content['title'] ?? "Politique de Confidentialité")
    @section('description', $metaDescription ?? "Politique de confidentialité de la plateforme REZI.")

    <div class="min-h-screen bg-gray-50">
        {{-- Header --}}
        <div class="bg-white border-b border-gray-200">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
                <nav class="text-sm text-gray-500 mb-4">
                    <a href="{{ route('home') }}" class="hover:text-[#ff385c] transition">Accueil</a>
                    <span class="mx-2">›</span>
                    <span class="text-gray-900">Politique de confidentialité</span>
                </nav>
                <h1 class="text-3xl sm:text-4xl font-bold text-gray-900">{{ $content['title'] ?? "Politique de Confidentialité" }}</h1>
                <p class="mt-3 text-gray-500">Dernière mise à jour : {{ now()->format('d/m/Y') }}</p>
            </div>
        </div>

        {{-- Content --}}
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <div class="bg-white rounded-2xl shadow-sm p-8 sm:p-12 space-y-8">

                @foreach($content['sections'] ?? [] as $section)
                <section>
                    <h2 class="text-xl font-bold text-gray-900 mb-4">{{ $section['title'] }}</h2>
                    <div class="text-gray-600 leading-relaxed whitespace-pre-line">{{ $section['content'] }}</div>
                </section>
                @endforeach

                <section>
                    <h2 class="text-xl font-bold text-gray-900 mb-4">Contact</h2>
                    <p class="text-gray-600 leading-relaxed">
                        Pour toute question relative à vos données personnelles, contactez-nous à :
                        <a href="mailto:{{ config('rezi.company.email') }}" class="text-[#ff385c] hover:underline">{{ config('rezi.company.email') }}</a>
                    </p>
                </section>

            </div>
        </div>
    </div>
</x-app-layout>
