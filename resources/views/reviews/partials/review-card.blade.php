<div class="border-b border-gray-100 pb-6 last:border-0 last:pb-0">
    <div class="flex items-start gap-4">
        <!-- Avatar -->
        <div class="shrink-0">
            <img loading="lazy" src="{{ $review->user->getAvatarUrl() }}"
                 alt="{{ $review->user->name }}"
                 class="w-12 h-12 rounded-full object-cover">
        </div>

        <div class="flex-1 min-w-0">
            <!-- En-tête -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-2">
                <div>
                    <span class="font-medium text-gray-900">
                        {{ $review->user->name }}
                    </span>
                    @if($review->is_verified)
                        <span class="inline-flex items-center ml-2 text-xs text-[#F16A00]">
                            <svg class="w-4 h-4 mr-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            Vérifié
                        </span>
                    @endif
                </div>
                <span class="text-sm text-gray-500">{{ $review->created_at->translatedFormat('d F Y') }}</span>
            </div>

            <!-- Note -->
            <div class="flex items-center gap-2 mb-3">
                <div class="flex items-center">
                    @for($i = 1; $i <= 5; $i++)
                        <svg class="w-4 h-4 {{ $i <= $review->rating ? 'text-yellow-400' : 'text-gray-200' }}" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                        </svg>
                    @endfor
                </div>
                @if($review->stay_period_formatted)
                    <span class="text-sm text-gray-500">· Séjour : {{ $review->stay_period_formatted }}</span>
                @endif
            </div>

            <!-- Résidence (si showResidence) -->
            @if(isset($showResidence) && $showResidence && $review->residence)
                <a href="{{ route('residences.show', $review->residence) }}"
                   class="flex items-center gap-2 mb-3 text-sm text-gray-600 hover:text-[#F16A00]">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                    </svg>
                    {{ $review->residence->title }}
                </a>
            @endif

            <!-- Commentaire -->
            <p class="text-gray-700 leading-relaxed">{{ $review->comment }}</p>

            <!-- Photos -->
            @if($review->photos && count($review->photos) > 0)
                <div class="flex flex-wrap gap-2 mt-3">
                    @foreach($review->photos as $photo)
                        <img loading="lazy" src="{{ storage_url($photo) }}"
                             alt="Photo de l'avis"
                             class="w-20 h-20 rounded-lg object-cover cursor-pointer hover:opacity-90 transition"
                             onclick="openLightbox('{{ storage_url($photo) }}')">
                    @endforeach
                </div>
            @endif

            <!-- Réponse du propriétaire -->
            @if($review->owner_response)
                <div class="mt-4 pl-4 border-l-2 border-[#FFD0A3] bg-[#FFF4EB] rounded-r-lg p-3">
                    <div class="flex items-center gap-2 mb-2">
                        <span class="text-sm font-medium text-gray-900">Réponse du propriétaire</span>
                        <span class="text-xs text-gray-500">{{ $review->owner_response_at->translatedFormat('d F Y') }}</span>
                    </div>
                    <p class="text-sm text-gray-700">{{ $review->owner_response }}</p>
                </div>
            @endif

            <!-- Actions -->
            <div class="flex items-center gap-4 mt-4">
                @auth
                    <!-- Vote utile -->
                    <button onclick="toggleHelpful({{ $review->id }})"
                            class="helpful-btn-{{ $review->id }} flex items-center gap-1 text-sm {{ $review->hasUserVoted(Auth::user()) ? 'text-[#F16A00]' : 'text-gray-500 hover:text-[#F16A00]' }} transition"
                            data-voted="{{ $review->hasUserVoted(Auth::user()) ? 'true' : 'false' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017c-.163 0-.326-.02-.485-.06L7 20m7-10V5a2 2 0 00-2-2h-.095c-.5 0-.905.405-.905.905 0 .714-.211 1.412-.608 2.006L7 11v9m7-10h-2M7 20H5a2 2 0 01-2-2v-6a2 2 0 012-2h2.5"/>
                        </svg>
                        <span class="helpful-count-{{ $review->id }}">{{ $review->helpful_count }}</span>
                        <span>Utile</span>
                    </button>

                    <!-- Signaler -->
                    @if(Auth::id() !== $review->user_id)
                        <button onclick="openReportModal({{ $review->id }})"
                                class="flex items-center gap-1 text-sm text-gray-500 hover:text-red-500 transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9"/>
                            </svg>
                            Signaler
                        </button>
                    @endif
                @endauth

                @if($review->is_featured)
                    <span class="flex items-center gap-1 text-sm text-amber-600">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                        </svg>
                        Mis en avant
                    </span>
                @endif
            </div>
        </div>
    </div>
</div>


