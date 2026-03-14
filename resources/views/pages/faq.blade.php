<x-app-layout>
    @section('title', $metaTitle ?? $content['title'] ?? "FAQ - Questions Fréquentes")
    @section('description', $metaDescription ?? "Trouvez les réponses à toutes vos questions sur REZI.")

    <div class="min-h-screen bg-gray-50">
        {{-- Header --}}
        <div class="bg-linear-to-br from-orange-500 to-orange-600">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
                <nav class="text-sm text-orange-100 mb-4">
                    <a href="{{ route('home') }}" class="hover:text-white transition">Accueil</a>
                    <span class="mx-2">›</span>
                    <span class="text-white">FAQ</span>
                </nav>
                <h1 class="text-3xl sm:text-4xl font-bold text-white">{{ $content['title'] ?? "Questions Fréquentes" }}</h1>
                <p class="mt-3 text-orange-100">{{ $content['subtitle'] ?? "Trouvez rapidement les réponses à vos questions" }}</p>
            </div>
        </div>

        {{-- Content --}}
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            @foreach($content['categories'] ?? [] as $categoryIndex => $category)
            <div class="mb-12">
                <h2 class="text-2xl font-bold text-gray-900 mb-6 flex items-center">
                    <span class="w-8 h-8 bg-orange-100 text-orange-600 rounded-full flex items-center justify-center mr-3 text-sm font-bold">{{ $categoryIndex + 1 }}</span>
                    {{ $category['name'] }}
                </h2>
                
                <div class="space-y-4">
                    @foreach($category['questions'] ?? [] as $question)
                    <details class="bg-white rounded-xl shadow-sm group" x-data="{ open: false }">
                        <summary class="flex items-center justify-between p-6 cursor-pointer list-none" @click="open = !open">
                            <span class="font-medium text-gray-900 pr-4">{{ $question['question'] }}</span>
                            <svg class="w-5 h-5 text-gray-400 transition-transform duration-200" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </summary>
                        <div class="px-6 pb-6 text-gray-600 leading-relaxed whitespace-pre-line">
                            {{ $question['answer'] }}
                        </div>
                    </details>
                    @endforeach
                </div>
            </div>
            @endforeach

            {{-- Contact CTA --}}
            <div class="bg-white rounded-2xl shadow-sm p-8 text-center mt-12">
                <h3 class="text-xl font-bold text-gray-900 mb-2">Vous n'avez pas trouvé votre réponse ?</h3>
                <p class="text-gray-600 mb-6">Notre équipe est là pour vous aider</p>
                <a href="{{ route('pages.contact') }}" class="inline-flex items-center px-6 py-3 bg-orange-500 text-white font-medium rounded-xl hover:bg-orange-600 transition">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                    </svg>
                    Contactez-nous
                </a>
            </div>
        </div>
    </div>
</x-app-layout>
