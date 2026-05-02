<x-app-layout>
    @section('title', 'Écrire un avis - ' . $residence->title . ' - REZI')

    <div class="py-6">
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Breadcrumb -->
            <nav class="mb-6" aria-label="Breadcrumb">
                <ol class="flex items-center gap-2 text-sm">
                    <li><a href="{{ route('home') }}" class="text-gray-500 hover:text-gray-700">Accueil</a></li>
                    <li class="text-gray-400">/</li>
                    <li><a href="{{ route('residences.show', $residence) }}" class="text-gray-500 hover:text-gray-700">{{ Str::limit($residence->title, 30) }}</a></li>
                    <li class="text-gray-400">/</li>
                    <li class="text-gray-900">Écrire un avis</li>
                </ol>
            </nav>

            <div class="bg-white rounded-2xl shadow-sm p-6">
                <!-- Header -->
                <div class="mb-6">
                    <h1 class="text-2xl font-bold text-gray-900">Partagez votre expérience</h1>
                    <p class="text-gray-600 mt-1">Donnez votre avis sur <strong>{{ $residence->title }}</strong></p>
                </div>

                <form action="{{ route('reviews.store', $residence) }}" method="POST" x-data="reviewForm(@js(['rating_cleanliness' => old('rating_cleanliness', 0), 'rating_location' => old('rating_location', 0), 'rating_value' => old('rating_value', 0), 'rating_communication' => old('rating_communication', 0)]))">
                    @csrf

                    <!-- Notes par catégorie -->
                    <div class="space-y-6">
                        @foreach([
                            'rating_cleanliness' => ['label' => 'Propreté', 'description' => 'État général et propreté du logement'],
                            'rating_location' => ['label' => 'Emplacement', 'description' => 'Accessibilité, quartier, transports'],
                            'rating_value' => ['label' => 'Rapport qualité/prix', 'description' => 'Le prix est-il justifié ?'],
                            'rating_communication' => ['label' => 'Communication', 'description' => 'Réactivité et clarté du propriétaire']
                        ] as $field => $info)
                            <div>
                                <label class="block text-sm font-medium text-gray-900 mb-1">{{ $info['label'] }}</label>
                                <p class="text-xs text-gray-500 mb-2">{{ $info['description'] }}</p>
                                
                                <div class="flex items-center gap-1">
                                    @for($i = 1; $i <= 5; $i++)
                                        <button type="button" 
                                                @click="ratings.{{ $field }} = {{ $i }}"
                                                class="p-1 focus:outline-none transition-transform hover:scale-110">
                                            <svg class="w-8 h-8 transition-colors" 
                                                 :class="ratings.{{ $field }} >= {{ $i }} ? 'text-yellow-400' : 'text-gray-300'"
                                                 fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                            </svg>
                                        </button>
                                    @endfor
                                    <span class="ml-2 text-sm text-gray-600" x-text="ratingLabels[ratings.{{ $field }}]"></span>
                                </div>
                                <input type="hidden" name="{{ $field }}" :value="ratings.{{ $field }}">
                                @error($field)
                                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        @endforeach
                    </div>

                    <!-- Note moyenne calculée -->
                    <div class="mt-6 p-4 bg-[#fff0f3] rounded-xl">
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium text-primary-900">Note globale</span>
                            <div class="flex items-center gap-2">
                                <span class="text-2xl font-bold text-[#e00b41]" x-text="overallRating.toFixed(1)"></span>
                                <span class="text-[#e00b41]">/5</span>
                            </div>
                        </div>
                    </div>

                    <!-- Commentaire -->
                    <div class="mt-6">
                        <label for="comment" class="block text-sm font-medium text-gray-900 mb-1">Votre commentaire</label>
                        <p class="text-xs text-gray-500 mb-2">Partagez les détails de votre expérience (optionnel mais recommandé)</p>
                        <textarea name="comment" id="comment" rows="5" 
                                  class="w-full border-gray-300 rounded-xl focus:border-[#ff385c] focus:ring-[#ff385c]"
                                  placeholder="Décrivez votre séjour, les points positifs et négatifs...">{{ old('comment') }}</textarea>
                        @error('comment')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Actions -->
                    <div class="mt-6 flex items-center justify-between pt-6 border-t">
                        <a href="{{ route('residences.show', $residence) }}" class="text-gray-600 hover:text-gray-900 transition-colors">
                            Annuler
                        </a>
                        <button type="submit" 
                                class="inline-flex items-center gap-2 bg-[#e00b41] text-white px-6 py-3 rounded-xl hover:bg-[#b5083a] transition-colors"
                                :disabled="!isValid"
                                :class="{ 'opacity-50 cursor-not-allowed': !isValid }">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            Publier mon avis
                        </button>
                    </div>
                </form>
            </div>

            <!-- Info -->
            <div class="mt-4 p-4 bg-blue-50 rounded-xl">
                <div class="flex gap-3">
                    <svg class="w-5 h-5 text-blue-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <p class="text-sm text-blue-700">
                        Votre avis sera publié après validation par notre équipe. Les avis doivent être respectueux et basés sur une expérience réelle.
                    </p>
                </div>
            </div>
        </div>
    </div>

</x-app-layout>
